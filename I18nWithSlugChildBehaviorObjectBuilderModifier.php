<?php
class I18nWithSlugChildBehaviorObjectBuilderModifier
{
	protected $behavior, $table, $builder;

	public function __construct(I18nWithSlugChildBehavior $behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}

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

  protected function addSlugSetter()
  {
    return $this->behavior->renderTemplate('objectChildSlugSetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
      'objectClassname' => $this->builder->getNewStubObjectBuilder($this->behavior->getTable())->getClassname(),
    ));
  }

  protected function addSlugGetter()
  {
    return $this->behavior->renderTemplate('objectChildSlugGetter', array(
      'slugColumnName' => $this->behavior->getSlugColumn()->getPhpName(),
    ));
  }
}
