<?php
require_once dirname(__FILE__) . '/I18nWithSlugChildBehaviorObjectBuilderModifier.php';

class I18nWithSlugChildBehavior extends Behavior
{
  protected $tableModificationOrder = 71;

  protected
    $objectBuilderModifier,
    $queryBuilderModifier;


  public function getObjectBuilderModifier()
  {
    if (is_null($this->objectBuilderModifier))
    {
      $this->objectBuilderModifier = new I18nWithSlugChildBehaviorObjectBuilderModifier($this);
    }
    return $this->objectBuilderModifier;
  }

  public function getSlugColumn()
  {
    return $this->table->getColumn($this->getParameter('slug_column'));
  }
}
