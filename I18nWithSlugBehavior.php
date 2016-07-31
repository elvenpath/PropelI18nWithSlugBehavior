<?php
require_once dirname(__FILE__) . '/I18nWithSlugBehaviorObjectBuilderModifier.php';
require_once dirname(__FILE__) . '/I18nWithSlugBehaviorQueryBuilderModifier.php';
require_once dirname(__FILE__) . '/I18nWithSlugBehaviorPeerBuilderModifier.php';

/**
 * Allows translation of text columns through transparent one-to-many relationship
 * with slug on the translated object
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 *
 * @property Table $table
 */
class I18nWithSlugBehavior extends Behavior
{
    const DEFAULT_CULTURE = 'en_EN';

    // default parameters value
    protected $parameters = [
        'i18n_table' => '%TABLE%_i18n',
        'i18n_phpname' => '%PHPNAME%I18n',
        'i18n_columns' => '',
        'culture_column' => 'culture',
        'default_culture' => null,
        'culture_alias' => '',
        'slug_column' => 'slug',
        'slug_pattern' => '',
        'replace_pattern' => '/[^\\pL\\d]+/u', // Tip: use '/[^\\pL\\d]+/u' instead if you're in PHP5.3
        'replacement' => '-',
        'separator' => '-',
        'permanent' => 'false',
        'disabled' => false,
    ];

    protected $buildProperties = null;

    protected $tableModificationOrder = 70;

    /** @var  I18nWithSlugBehaviorObjectBuilderModifier */
    protected $objectBuilderModifier;

    /** @var  I18nWithSlugBehaviorQueryBuilderModifier */
    protected $queryBuilderModifier;

    /** @var  I18nWithSlugBehaviorPeeryBuilderModifier */
    protected $peerBuilderModifier;

    /** @var  Table */
    protected $i18nTable;


    public function modifyDatabase()
    {
        /** @var Table[] $tables */
        $tables = $this->getDatabase()->getTables();
        foreach ($tables as $table) {
            if ($table->hasBehavior('i18n_with_slug') && !$table->getBehavior('i18n_with_slug')->getParameter(
                    'default_culture'
                )
            ) {
                $table->getBehavior('i18n_with_slug')->addParameter(
                    [
                        'name' => 'default_culture',
                        'value' => $this->getParameter('default_culture'),
                    ]
                );
            }
        }
    }


    public function modifyTable()
    {
        $this->addI18nTable();
        $this->relateI18nTableToMainTable();
        $this->addCultureColumnToI18n();
        $this->addSlugColumnToI18n();
        $this->moveI18nColumns();
    }


    protected function addI18nTable()
    {
        $table = $this->getTable();
        $database = $table->getDatabase();
        $i18nTableName = $this->getI18nTableName();
        if ($database->hasTable($i18nTableName)) {
            $this->i18nTable = $database->getTable($i18nTableName);
        } else {
            $this->i18nTable = $database->addTable(
                [
                    'name' => $i18nTableName,
                    'phpName' => $this->getI18nTablePhpName(),
                    'package' => $table->getPackage(),
                    'schema' => $table->getSchema(),
                    'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
                ]
            );
            // every behavior adding a table should re-execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                /** @var $behavior Behavior */
                $behavior->modifyDatabase();
            }
        }
    }


    protected function relateI18nTableToMainTable()
    {
        $table = $this->getTable();
        $i18nTable = $this->i18nTable;

        /** @var Column[] $pks */
        $pks = $this->getTable()->getPrimaryKey();
        if (count($pks) > 1) {
            throw new EngineException('The i18n behavior does not support tables with composite primary keys');
        }
        foreach ($pks as $column) {
            if (!$i18nTable->hasColumn($column->getName())) {
                $column = clone $column;
                $column->setAutoIncrement(false);
                $i18nTable->addColumn($column);
            }
        }
        if (in_array($table->getName(), $i18nTable->getForeignTableNames())) {
            return;
        }
        $fk = new ForeignKey();
        $fk->setForeignTableCommonName($table->getCommonName());
        $fk->setForeignSchemaName($table->getSchema());
        $fk->setDefaultJoin('LEFT JOIN');
        $fk->setOnDelete(ForeignKey::CASCADE);
        $fk->setOnUpdate(ForeignKey::CASCADE);
        foreach ($pks as $column) {
            $fk->addReference($column->getName(), $column->getName());
        }
        $i18nTable->addForeignKey($fk);
    }


    protected function addCultureColumnToI18n()
    {
        $cultureColumnName = $this->getCultureColumnName();
        if (!$this->i18nTable->hasColumn($cultureColumnName)) {
            $this->i18nTable->addColumn(
                [
                    'name' => $cultureColumnName,
                    'type' => PropelTypes::VARCHAR,
                    'size' => 5,
                    'default' => $this->getDefaultCulture(),
                    'primaryKey' => 'true',
                ]
            );

            $index = new Index();
            $index->addColumn(['name' => $this->getParameter('culture_column')]);
            $index->resetColumnSize();
            $this->i18nTable->addIndex($index);
        }
    }


    /**
     * Moves i18n columns from the main table to the i18n table
     */
    protected function moveI18nColumns()
    {
        $table = $this->getTable();
        $i18nTable = $this->i18nTable;
        foreach ($this->getI18nColumnNamesFromConfig() as $columnName) {
            if (!$i18nTable->hasColumn($columnName)) {
                if (!$table->hasColumn($columnName)) {
                    throw new EngineException(sprintf(
                        'No column named %s found in table %s',
                        $columnName,
                        $table->getName()
                    ));
                }
                $column = $table->getColumn($columnName);
                // add the column
                $i18nColumn = $i18nTable->addColumn(clone $column);
                // add related validators
                if ($validator = $column->getValidator()) {
                    $i18nValidator = $i18nTable->addValidator(clone $validator);
                }
                // FIXME: also move FKs, and indices on this column
            }
            if ($table->hasColumn($columnName)) {
                $table->removeColumn($columnName);
                $table->removeValidatorForColumn($columnName);
            }
        }
    }


    protected function addSlugColumnToI18n()
    {
        if (!$this->i18nTable->hasColumn($this->getParameter('slug_column'))) {
            $this->i18nTable->addColumn(
                [
                    'name' => $this->getParameter('slug_column'),
                    'type' => 'VARCHAR',
                    'size' => 255,
                ]
            );

            // add a unique to column
            $unique = new Unique();
            $unique->addColumn($this->i18nTable->getColumn($this->getParameter('slug_column')));
            $unique->addColumn($this->i18nTable->getColumn($this->getParameter('culture_column')));
            $this->i18nTable->addUnique($unique);
        }
    }


    /**
     * @return string
     */
    protected function getI18nTableName()
    {
        return $this->replaceTokens($this->getParameter('i18n_table'));
    }


    /**
     * @return string-
     */
    protected function getI18nTablePhpName()
    {
        return $this->replaceTokens($this->getParameter('i18n_phpname'));
    }


    /**
     * @return string
     */
    protected function getCultureColumnName()
    {
        return $this->replaceTokens($this->getParameter('culture_column'));
    }


    /**
     * @return array
     */
    protected function getI18nColumnNamesFromConfig()
    {
        $columnNames = explode(',', $this->getParameter('i18n_columns'));
        $columnNames[] = $this->getParameter('slug_column');
        foreach ($columnNames as $key => $columnName) {
            if ($columnName = trim($columnName)) {
                $columnNames[$key] = $columnName;
            } else {
                unset($columnNames[$key]);
            }
        }

        return $columnNames;
    }


    /**
     * @return null|string
     */
    public function getDefaultCulture()
    {
        if (!$defaultCulture = $this->getParameter('default_culture')) {
            $defaultCulture = self::DEFAULT_CULTURE;
        }

        return $defaultCulture;
    }


    /**
     * Returns the current table's i18n translation table.
     *
     * @return Table
     */
    public function getI18nTable()
    {
        return $this->i18nTable;
    }


    /**
     * @return ForeignKey
     */
    public function getI18nForeignKey()
    {
        /** @var ForeignKey[] $fks */
        $fks = $this->i18nTable->getForeignKeys();
        foreach ($fks as $fk) {
            if ($fk->getForeignTableName() == $this->table->getName()) {
                return $fk;
            }
        }

        return null;
    }


    /**
     * @return Column
     */
    public function getCultureColumn()
    {
        return $this->getI18nTable()->getColumn($this->getCultureColumnName());
    }


    /**
     * @return Column
     */
    public function getSlugColumn()
    {
        return $this->getI18nTable()->getColumn($this->getParameter('slug_column'));
    }


    /**
     * @return array|Column[]
     */
    public function getI18nColumns()
    {
        $columns = [];
        $i18nTable = $this->getI18nTable();
        if ($columnNames = $this->getI18nColumnNamesFromConfig()) {
            // Strategy 1: use the i18n_columns parameter
            foreach ($columnNames as $columnName) {
                $columns [] = $i18nTable->getColumn($columnName);
            }
        } else {
            // strategy 2: use the columns of the i18n table
            // warning: does not work when database behaviors add columns to all tables
            // (such as timestampable behavior)
            /** @var Column[] $columns */
            $columns = $i18nTable->getColumns();
            foreach ($columns as $column) {
                if (!$column->isPrimaryKey()) {
                    $columns [] = $column;
                }
            }
        }

        return $columns;
    }


    /**
     * @param $string
     *
     * @return string
     */
    public function replaceTokens($string)
    {
        $table = $this->getTable();

        return strtr(
            $string,
            [
                '%TABLE%' => $table->getName(),
                '%PHPNAME%' => $table->getPhpName(),
            ]
        );
    }


    /**
     * @return I18nWithSlugBehaviorObjectBuilderModifier
     */
    public function getObjectBuilderModifier()
    {
        if (is_null($this->objectBuilderModifier)) {
            $this->objectBuilderModifier = new I18nWithSlugBehaviorObjectBuilderModifier($this);
        }

        return $this->objectBuilderModifier;
    }


    /**
     * @return I18nWithSlugBehaviorQueryBuilderModifier
     */
    public function getQueryBuilderModifier()
    {
        if (is_null($this->queryBuilderModifier)) {
            $this->queryBuilderModifier = new I18nWithSlugBehaviorQueryBuilderModifier($this);
        }

        return $this->queryBuilderModifier;
    }


    /**
     * @return I18nWithSlugBehaviorPeerBuilderModifier
     */
    public function getPeerBuilderModifier()
    {
        if (is_null($this->peerBuilderModifier)) {
            $this->peerBuilderModifier = new I18nWithSlugBehaviorPeerBuilderModifier($this);
        }

        return $this->peerBuilderModifier;
    }


    /**
     * Get the getter of the column of the behavior
     *
     * @return string The related getter, e.g. 'getSlug'
     */
    public function getColumnGetter()
    {
        return 'get' . $this->i18nTable->getColumn($this->getParameter('slug_column'))->getPhpName();
    }


    /**
     * Get the setter of the column of the behavior
     *
     * @return string The related setter, e.g. 'setSlug'
     */
    public function getColumnSetter()
    {
        return 'set' . $this->i18nTable->getColumn($this->getParameter('slug_column'))->getPhpName();
    }


    /**
     * Returns a build property from propel.ini.
     *
     * @param string $name
     *
     * @return string
     */
    public function getBuildProperty($name)
    {
        if (null === $this->buildProperties) {
            $this->buildProperties = new Properties();
            $this->buildProperties->load(new PhingFile(sfConfig::get('sf_config_dir') . '/propel.ini'));
        }

        return $this->buildProperties->getProperty($name);
    }


    /**
     * Returns true if the current behavior has been disabled.
     *
     * @return boolean
     */
    protected function isDisabled()
    {
        return isset($this->parameters['disabled']) && 'true' == $this->getParameter('disabled');
    }


    /**
     * Returns the column on the current model referenced by the translation model.
     *
     * @return Column
     */
    public function getLocalColumn()
    {
        $columns = $this->getI18nForeignKey()->getForeignColumns();

        return $this->getTable()->getColumn($columns[0]);
    }


    /**
     * Returns the column on the translation table the references the current model.
     *
     * @return Column
     */
    public function getForeignColumn()
    {
        $columns = $this->getI18nForeignKey()->getLocalColumns();

        return $this->getI18nTable()->getColumn($columns[0]);
    }
}
