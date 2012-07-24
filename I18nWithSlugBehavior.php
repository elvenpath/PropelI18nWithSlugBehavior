<?php
require_once dirname(__FILE__) . '/I18nWithSlugBehaviorObjectBuilderModifier.php';
require_once dirname(__FILE__) . '/I18nWithSlugBehaviorQueryBuilderModifier.php';
require_once dirname(__FILE__) . '/I18nWithSlugBehaviorPeerBuilderModifier.php';

/**
 * Allows translation of text columns through transparent one-to-many relationship
 * with slug on the translated object
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 */
class I18nWithSlugBehavior extends Behavior
{
  const DEFAULT_CULTURE = 'en_EN';

  // default parameters value
  protected $parameters = array(
    'i18n_table'      => '%TABLE%_i18n',
    'i18n_phpname'    => '%PHPNAME%I18n',
    'i18n_columns'    => '',
    'culture_column'   => 'culture',
    'default_culture'  => null,
    'culture_alias'    => '',
    'slug_column'     => 'slug',
    'slug_pattern'    => '',
    'replace_pattern' => '/\W+/', // Tip: use '/[^\\pL\\d]+/u' instead if you're in PHP5.3
    'replacement'     => '-',
    'separator'       => '-',
    'permanent'       => 'false'
  );

  protected $tableModificationOrder = 70;

  protected
    $objectBuilderModifier,
    $queryBuilderModifier,
    $peerBuilderModifier,
    $i18nTable;

  public function modifyDatabase()
  {

    foreach ($this->getDatabase()->getTables() as $table)
    {
      if ($table->hasBehavior('i18n_with_slug') && !$table->getBehavior('i18n_with_slug')->getParameter('default_culture'))
      {
        $table->getBehavior('i18n_with_slug')->addParameter(array(
          'name'  => 'default_culture',
          'value' => $this->getParameter('default_culture')
        ));
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
    if ($database->hasTable($i18nTableName))
    {
      $this->i18nTable = $database->getTable($i18nTableName);
    }
    else
    {
      $this->i18nTable = $database->addTable(array(
        'name'      => $i18nTableName,
        'phpName'   => $this->getI18nTablePhpName(),
        'package'   => $table->getPackage(),
        'schema'    => $table->getSchema(),
        'namespace' => $table->getNamespace() ? '\\' . $table->getNamespace() : null,
      ));
      // every behavior adding a table should re-execute database behaviors
      foreach ($database->getBehaviors() as $behavior)
      {
        /** @var $behavior Behavior */
        $behavior->modifyDatabase();
      }
    }
  }

  protected function relateI18nTableToMainTable()
  {
    $table = $this->getTable();
    $i18nTable = $this->i18nTable;
    $pks = $this->getTable()->getPrimaryKey();
    if (count($pks) > 1)
    {
      throw new EngineException('The i18n behavior does not support tables with composite primary keys');
    }
    foreach ($pks as $column)
    {
      if (!$i18nTable->hasColumn($column->getName()))
      {
        $column = clone $column;
        $column->setAutoIncrement(false);
        $i18nTable->addColumn($column);
      }
    }
    if (in_array($table->getName(), $i18nTable->getForeignTableNames()))
    {
      return;
    }
    $fk = new ForeignKey();
    $fk->setForeignTableCommonName($table->getCommonName());
    $fk->setForeignSchemaName($table->getSchema());
    $fk->setDefaultJoin('LEFT JOIN');
    $fk->setOnDelete(ForeignKey::CASCADE);
    $fk->setOnUpdate(ForeignKey::CASCADE);
    foreach ($pks as $column)
    {
      $fk->addReference($column->getName(), $column->getName());
    }
    $i18nTable->addForeignKey($fk);
  }

  protected function addCultureColumnToI18n()
  {
    $cultureColumnName = $this->getCultureColumnName();
    if (!$this->i18nTable->hasColumn($cultureColumnName))
    {
      $this->i18nTable->addColumn(array(
        'name'       => $cultureColumnName,
        'type'       => PropelTypes::VARCHAR,
        'size'       => 5,
        'default'    => $this->getDefaultCulture(),
        'primaryKey' => 'true',
      ));
    }
  }

  /**
   * Moves i18n columns from the main table to the i18n table
   */
  protected function moveI18nColumns()
  {
    $table = $this->getTable();
    $i18nTable = $this->i18nTable;
    foreach ($this->getI18nColumnNamesFromConfig() as $columnName)
    {
      if (!$i18nTable->hasColumn($columnName))
      {
        if (!$table->hasColumn($columnName))
        {
          throw new EngineException(sprintf('No column named %s found in table %s', $columnName, $table->getName()));
        }
        $column = $table->getColumn($columnName);
        // add the column
        $i18nColumn = $i18nTable->addColumn(clone $column);
        // add related validators
        if ($validator = $column->getValidator())
        {
          $i18nValidator = $i18nTable->addValidator(clone $validator);
        }
        // FIXME: also move FKs, and indices on this column
      }
      if ($table->hasColumn($columnName))
      {
        $table->removeColumn($columnName);
        $table->removeValidatorForColumn($columnName);
      }
    }
  }

  protected function addSlugColumnToI18n()
  {
    $slugColumnName = $this->getSlugColumnName();
    if (!$this->i18nTable->hasColumn($slugColumnName))
    {
      $this->i18nTable->addColumn(array(
        'name' => $slugColumnName,
        'type' => 'VARCHAR',
        'size' => 255
      ));

      // add a unique to column
      $unique = new Unique();
      $unique->addColumn($this->i18nTable->getColumn($this->getParameter('slug_column')));
      $unique->addColumn($this->i18nTable->getColumn($this->getParameter('culture_column')));
      $this->i18nTable->addUnique($unique);
    }


  }

  protected function getI18nTableName()
  {
    return $this->replaceTokens($this->getParameter('i18n_table'));
  }

  protected function getI18nTablePhpName()
  {
    return $this->replaceTokens($this->getParameter('i18n_phpname'));
  }

  protected function getCultureColumnName()
  {
    return $this->replaceTokens($this->getParameter('culture_column'));
  }
  protected function getSlugColumnName()
  {
    return $this->replaceTokens($this->getParameter('slug_column'));
  }

  protected function getI18nColumnNamesFromConfig()
  {
    $columnNames = explode(',', $this->getParameter('i18n_columns'));
    $columnNames[] = $this->getParameter('slug_column');
    foreach ($columnNames as $key => $columnName)
    {
      if ($columnName = trim($columnName))
      {
        $columnNames[$key] = $columnName;
      }
      else
      {
        unset($columnNames[$key]);
      }
    }

    return $columnNames;
  }

  public function getDefaultCulture()
  {
    if (!$defaultCulture = $this->getParameter('default_culture'))
    {
      $defaultCulture = self::DEFAULT_CULTURE;
    }
    return $defaultCulture;
  }

  public function getI18nTable()
  {
    return $this->i18nTable;
  }

  public function getI18nForeignKey()
  {
    foreach ($this->i18nTable->getForeignKeys() as $fk)
    {
      if ($fk->getForeignTableName() == $this->table->getName())
      {
        return $fk;
      }
    }
  }

  public function getCultureColumn()
  {
    return $this->getI18nTable()->getColumn($this->getCultureColumnName());
  }
  public function getSlugColumn()
  {
    return $this->getI18nTable()->getColumn($this->getSlugColumnName());
  }

  public function getI18nColumns()
  {
    $columns = array();
    $i18nTable = $this->getI18nTable();
    if ($columnNames = $this->getI18nColumnNamesFromConfig())
    {
      // Strategy 1: use the i18n_columns parameter
      foreach ($columnNames as $columnName)
      {
        $columns [] = $i18nTable->getColumn($columnName);
      }
    }
    else
    {
      // strategy 2: use the columns of the i18n table
      // warning: does not work when database behaviors add columns to all tables
      // (such as timestampable behavior)
      foreach ($i18nTable->getColumns() as $column)
      {
        if (!$column->isPrimaryKey())
        {
          $columns [] = $column;
        }
      }
    }

    return $columns;
  }

  public function replaceTokens($string)
  {
    $table = $this->getTable();
    return strtr($string, array(
      '%TABLE%'   => $table->getName(),
      '%PHPNAME%' => $table->getPhpName(),
    ));
  }

  public function getObjectBuilderModifier()
  {
    if (is_null($this->objectBuilderModifier))
    {
      $this->objectBuilderModifier = new I18nWithSlugBehaviorObjectBuilderModifier($this);
    }
    return $this->objectBuilderModifier;
  }

  public function getQueryBuilderModifier()
  {
    if (is_null($this->queryBuilderModifier))
    {
      $this->queryBuilderModifier = new I18nWithSlugBehaviorQueryBuilderModifier($this);
    }
    return $this->queryBuilderModifier;
  }

  public function getPeerBuilderModifier()
  {
    if (is_null($this->peerBuilderModifier))
    {
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
}
