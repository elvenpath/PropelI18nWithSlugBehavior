<?php
require_once dirname(__FILE__) . '/I18nWithSlugChildBehaviorObjectBuilderModifier.php';

class I18nWithSlugChildBehavior extends Behavior
{
  protected $tableModificationOrder = 71;

  protected
    $objectBuilderModifier,
    $queryBuilderModifier;


  /**
   * @return I18nWithSlugChildBehaviorObjectBuilderModifier
   */
  public function getObjectBuilderModifier()
  {
    if (is_null($this->objectBuilderModifier))
    {
      $this->objectBuilderModifier = new I18nWithSlugChildBehaviorObjectBuilderModifier($this);
    }
    return $this->objectBuilderModifier;
  }

  /**
   * @return I18nWithSlugChildBehaviorQueryBuilderModifier
   */
  public function getQueryBuilderModifier()
  {
    if (is_null($this->queryBuilderModifier))
    {
      $this->queryBuilderModifier = new I18nWithSlugChildBehaviorQueryBuilderModifier($this);
    }
    return $this->queryBuilderModifier;
  }

  /**
   * @return Column
   */
  public function getSlugColumn()
  {
    return $this->table->getColumn($this->getParameter('slug_column'));
  }

  /**
   * Returns the foreign key that references the translated model.
   *
   * @throws Exception
   * @return ForeignKey
   *
   */
  public function getForeignKey()
  {
    foreach ($this->getTable()->getForeignKeys() as $fk)
    {
      /** @var $fk ForeignKey  */
      /** @var $behaviors array|Behavior[] */
      $behaviors = $fk->getForeignTable()->getBehaviors();
      if (isset($behaviors['i18n_with_slug']))
      {
        return $fk;
      }
    }

    throw new Exception('The foreign key that references the I18N model could not be found.');
  }

  public function getCultureColumn()
  {
    return $this->table->getColumn($this->getParameter('culture_column'));
  }

  public function getDefaultCulture()
  {
    return $this->getParameter('default_culture');
  }
}
