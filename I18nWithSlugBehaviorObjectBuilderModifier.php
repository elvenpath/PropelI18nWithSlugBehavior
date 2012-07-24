<?php

/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the object builder.
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 */
class I18nWithSlugBehaviorObjectBuilderModifier
{
	protected $behavior, $table, $builder;

	public function __construct(I18nWithSlugBehavior $behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}

	public function postDelete($builder)
	{
		$this->builder = $builder;
		if (!$builder->getPlatform()->supportsNativeDeleteTrigger() && !$builder->getBuildProperty('emulateForeignKeyConstraints')) {
			$i18nTable = $this->behavior->getI18nTable();
			return $this->behavior->renderTemplate('objectPostDelete', array(
				'i18nQueryName'    => $builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
				'objectClassname' => $builder->getNewStubObjectBuilder($this->behavior->getTable())->getClassname(),
			));
		}
	}

  /**
   * Add code in ObjectBuilder::preSave
   *
   * @param $builder ObjectBuilder
   * @return string The code to put at the hook
   */
  public function preSave($builder)
  {
    $const = $builder->getColumnConstant($this->behavior->getI18nTable()->getColumn($this->behavior->getParameter('slug_column')));

    return $this->behavior->renderTemplate('objectPreSave', array(
      'defaultCulture'   => $this->behavior->getDefaultCulture(),
      'const' => $const,
      'columnGetter' => $this->behavior->getColumnGetter(),
      'columnSetter' => $this->behavior->getColumnSetter(),
      'permanent' => $this->behavior->getParameter('permanent')
    ));
  }

	public function objectAttributes($builder)
	{
		return $this->behavior->renderTemplate('objectAttributes', array(
			'defaultCulture'   => $this->behavior->getDefaultCulture(),
			'objectClassname' => $builder->getNewStubObjectBuilder($this->behavior->getI18nTable())->getClassname(),
		));
	}

	public function objectClearReferences($builder)
	{
		return $this->behavior->renderTemplate('objectClearReferences', array(
			'defaultCulture'   => $this->behavior->getDefaultCulture(),
		));
	}

	public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script = '';
		$script .= $this->addSetCulture();
		$script .= $this->addGetCulture();
		if ($alias = $this->behavior->getParameter('culture_alias')) {
			$script .= $this->addGetCultureAlias($alias);
			$script .= $this->addSetCultureAlias($alias);
		}
		$script .= $this->addGetTranslation();
		$script .= $this->addRemoveTranslation();
		$script .= $this->addGetCurrentTranslation();
		foreach ($this->behavior->getI18nColumns() as $column) {
			$script .= $this->addTranslatedColumnGetter($column);
			$script .= $this->addTranslatedColumnSetter($column);
		}

    $script .= $this->addSlugSetter();
    $script .= $this->addSlugGetter();
    $script .= $this->addCreateSlug();

    return $script;
	}

	protected function addSetCulture()
	{
		return $this->behavior->renderTemplate('objectSetCulture', array(
			'objectClassname' => $this->builder->getStubObjectBuilder($this->table)->getClassname(),
			'defaultCulture'    => $this->behavior->getDefaultCulture(),
		));
	}

	protected function addGetCulture()
	{
		return $this->behavior->renderTemplate('objectGetCulture');
	}

	protected function addSetCultureAlias($alias)
	{
		return $this->behavior->renderTemplate('objectSetCultureAlias', array(
			'objectClassname' => $this->builder->getStubObjectBuilder($this->table)->getClassname(),
			'defaultCulture'    => $this->behavior->getDefaultCulture(),
			'alias'            => ucfirst($alias),
		));
	}

	protected function addGetCultureAlias($alias)
	{
		return $this->behavior->renderTemplate('objectGetCultureAlias', array(
			'alias' => ucfirst($alias),
		));
	}

	protected function addGetTranslation()
	{
		$i18nTable = $this->behavior->getI18nTable();
		$fk = $this->behavior->getI18nForeignKey();
		return $this->behavior->renderTemplate('objectGetTranslation', array(
			'i18nTablePhpName' => $this->builder->getNewStubObjectBuilder($i18nTable)->getClassname(),
			'defaultCulture'    => $this->behavior->getDefaultCulture(),
			'i18nListVariable' => $this->builder->getRefFKCollVarName($fk),
			'cultureColumnName' => $this->behavior->getCultureColumn()->getPhpName(),
			'i18nQueryName'    => $this->builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
			'i18nSetterMethod' => $this->builder->getRefFKPhpNameAffix($fk, $plural = false),
		));
	}

	protected function addRemoveTranslation()
	{
		$i18nTable = $this->behavior->getI18nTable();
		$fk = $this->behavior->getI18nForeignKey();
		return $this->behavior->renderTemplate('objectRemoveTranslation', array(
			'objectClassname' => $this->builder->getStubObjectBuilder($this->table)->getClassname(),
			'defaultCulture'    => $this->behavior->getDefaultCulture(),
			'i18nQueryName'    => $this->builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
			'i18nCollection'   => $this->builder->getRefFKCollVarName($fk),
			'cultureColumnName' => $this->behavior->getCultureColumn()->getPhpName(),
		));
	}

	protected function addGetCurrentTranslation()
	{
		return $this->behavior->renderTemplate('objectGetCurrentTranslation', array(
			'i18nTablePhpName' => $this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable())->getClassname(),
		));
	}

	// FIXME: the connection used by getCurrentTranslation in the generated code
	// cannot be specified by the user
	protected function addTranslatedColumnGetter(Column $column)
	{
		$objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
		$comment = '';
		$functionStatement = '';
		if ($column->getType() === PropelTypes::DATE || $column->getType() === PropelTypes::TIME || $column->getType() === PropelTypes::TIMESTAMP) {
			$objectBuilder->addTemporalAccessorComment($comment, $column);
			$objectBuilder->addTemporalAccessorOpen($functionStatement, $column);
		} else {
			$objectBuilder->addDefaultAccessorComment($comment, $column);
			$objectBuilder->addDefaultAccessorOpen($functionStatement, $column);
		}
		$comment = preg_replace('/^\t/m', '', $comment);
		$functionStatement = preg_replace('/^\t/m', '', $functionStatement);
		preg_match_all('/\$[a-z]+/i', $functionStatement, $params);
		return $this->behavior->renderTemplate('objectTranslatedColumnGetter', array(
			'comment'           => $comment,
			'functionStatement' => $functionStatement,
			'columnPhpName'     => $column->getPhpName(),
			'params'            => implode(', ', $params[0]),
		));
	}

	// FIXME: the connection used by getCurrentTranslation in the generated code
	// cannot be specified by the user
	protected function addTranslatedColumnSetter(Column $column)
	{
		$i18nTablePhpName = $this->builder->getNewStubObjectBuilder($this->behavior->getI18nTable())->getClassname();
		$tablePhpName = $this->builder->getStubObjectBuilder()->getClassname();
		$objectBuilder = $this->builder->getNewObjectBuilder($this->behavior->getI18nTable());
		$comment = '';
		$functionStatement = '';
		if ($column->getType() === PropelTypes::DATE || $column->getType() === PropelTypes::TIME || $column->getType() === PropelTypes::TIMESTAMP) {
			$objectBuilder->addTemporalMutatorComment($comment, $column);
			$objectBuilder->addMutatorOpenOpen($functionStatement, $column);
		} else {
			$objectBuilder->addMutatorComment($comment, $column);
			$objectBuilder->addMutatorOpenOpen($functionStatement, $column);
		}
		$comment = preg_replace('/^\t/m', '', $comment);
		$comment = str_replace('@return     ' . $i18nTablePhpName, '@return     ' . $tablePhpName, $comment);
		$functionStatement = preg_replace('/^\t/m', '', $functionStatement);
		preg_match_all('/\$[a-z]+/i', $functionStatement, $params);
		return $this->behavior->renderTemplate('objectTranslatedColumnSetter', array(
			'comment'           => $comment,
			'functionStatement' => $functionStatement,
			'columnPhpName'     => $column->getPhpName(),
			'params'            => implode(', ', $params[0]),
		));
	}

	public function objectFilter(&$script, $builder)
	{
		$i18nTable = $this->behavior->getI18nTable();
		$i18nTablePhpName = $this->builder->getNewStubObjectBuilder($i18nTable)->getUnprefixedClassname();
		$cultureColumnName = $this->behavior->getCultureColumn()->getPhpName();
		$pattern = '/public function add' . $i18nTablePhpName . '.*[\r\n]\s*\{/';
		$addition = "
		if (\$l && \$culture = \$l->get$cultureColumnName()) {
			\$this->setCulture(\$culture);
			\$this->currentTranslations[\$culture] = \$l;
		}";
		$replacement = "\$0$addition";
		$script = preg_replace($pattern, $replacement, $script);
	}

  protected function addSlugSetter()
  {
    return $this->behavior->renderTemplate('objectSlugSetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
    ));
  }

  protected function addSlugGetter()
  {
    return $this->behavior->renderTemplate('objectSlugGetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
    ));
  }

  protected function addCreateSlug()
  {
    $i18nTable = $this->behavior->getI18nTable();

    return $this->behavior->renderTemplate('objectCreateSlug', array(
      'replacement' => $this->behavior->getParameter('replacement'),
      'pattern' => $this->behavior->getParameter('slug_pattern'),
      'replace_pattern' => $this->behavior->getParameter('replace_pattern'),
      'size' => $this->behavior->getI18nTable()->getColumn($this->behavior->getParameter('slug_column'))->getSize(),
      'separator' => $this->behavior->getParameter('separator'),
      'i18nQueryName'    => $this->builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
      'softDeleteBehaviour'    => $i18nTable->hasBehavior('soft_delete'),
      'cultureColumnName' => $this->behavior->getCultureColumn()->getPhpName(),
      'defaultCulture'    => $this->behavior->getDefaultCulture(),
    ));

  }
}
