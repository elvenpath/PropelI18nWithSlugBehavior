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

    return $script;
	}

  /**
   * @return string
   */
  protected function addSlugSetter()
  {
    return $this->behavior->renderTemplate('objectChildSlugSetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
      'objectClassname' => $this->builder->getNewStubObjectBuilder($this->behavior->getTable())->getClassname(),
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
}
