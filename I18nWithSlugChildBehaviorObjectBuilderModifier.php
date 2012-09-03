<?php
class I18nWithSlugChildBehaviorObjectBuilderModifier
{
	protected $behavior, $table, $builder;

  /**
   * @param I18nWithSlugChildBehavior $behavior
   */
  public function __construct(I18nWithSlugChildBehavior $behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}

  /**
   * @param $builder
   *
   * @return string
   */
  public function objectMethods($builder)
	{
		$this->builder = $builder;
		$script = '';

    if ($this->behavior->getParameter('slug_column') <> 'slug')
    {
      $script .= $this->addSlugSetter();
      $script .= $this->addSlugGetter();
    }

    $script .= $this->addCreateSlug();
    $script .= $this->addParentGetters();

    return $script;
	}

  /**
   * Add code in ObjectBuilder::preSave
   *
   * @param $builder ObjectBuilder
   * @return string The code to put at the hook
   */
  public function preSave($builder)
  {
    $const = $builder->getColumnConstant($this->behavior->getTable()->getColumn($this->behavior->getParameter('slug_column')));

    return $this->behavior->renderTemplate('objectChildPreSave', array(
      'defaultCulture'   => $this->behavior->getDefaultCulture(),
      'const' => $const,
      'columnGetter' => $this->behavior->getColumnGetter(),
      'columnSetter' => $this->behavior->getColumnSetter(),
      'permanent' => $this->behavior->getParameter('permanent')
    ));
  }

  /**
   * @return string
   */
  protected function addSlugSetter()
  {
    return $this->behavior->renderTemplate('objectChildSlugSetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
      'objectClassname' => $this->behavior->getTable()->getPhpName()
    ));
  }

  /**
   * @return string
   */
  protected function addSlugGetter()
  {
    return $this->behavior->renderTemplate('objectChildSlugGetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
    ));
  }

  /**
   * @return string
   */
  protected function addCreateSlug()
  {
    $i18nTable = $this->behavior->getTable();

    return $this->behavior->renderTemplate('objectChildCreateSlug', array(
      'replacement' => $this->behavior->getParameter('replacement'),
      'pattern' => $this->behavior->getParameter('slug_pattern'),
      'replace_pattern' => $this->behavior->getParameter('replace_pattern'),
      'size' => $this->behavior->getTable()->getColumn($this->behavior->getParameter('slug_column'))->getSize(),
      'separator' => $this->behavior->getParameter('separator'),
      'i18nQueryName'    => $this->builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
      'softDeleteBehaviour'    => $i18nTable->hasBehavior('soft_delete'),
      'cultureColumnName' => $this->behavior->getCultureColumn()->getPhpName(),
      'defaultCulture'    => $this->behavior->getDefaultCulture(),
    ));
  }

  /**
   * @return string
   */
  protected function addParentGetters()
  {
    return $this->behavior->renderTemplate('objectChildAddParentGetters', array(
      'columns' => $this->behavior->getParameter('parent_columns'),
      'callerClassName' => $this->behavior->getParameter('caller_class_name'),
      'table' => $this->behavior->getTable()
    ));
  }
}
