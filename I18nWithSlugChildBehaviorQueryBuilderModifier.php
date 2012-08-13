<?php
/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the query builder.
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 */
class I18nWithSlugChildBehaviorQueryBuilderModifier
{
	protected $behavior, $table, $builder;

  /**
   * @param I18nWithSlugBehavior $behavior
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
  public function queryMethods($builder)
	{
		$this->builder = $builder;

		$script = '';
		$script .= $this->addFilterBySlug();
    $script .= $this->addFindOneBySlug();

		return $script;
	}

  /**
   * @return string
   */
  protected function addFilterBySlug()
  {
    $table = $this->behavior->getTable();
    return $this->behavior->renderTemplate('queryChildFilterBySlug', array(
      'queryClass'           => $this->builder->getNewStubQueryBuilder($this->table)->getClassname(),
      'defaultCulture'        => $this->behavior->getDefaultCulture(),
      'cultureColumn'         => $this->behavior->getCultureColumn()->getPhpName(),
      'slugColumn'         => $this->behavior->getSlugColumn()->getPhpName(),
    ));
  }

  /**
   * @return string
   */
  protected function addFindOneBySlug()
  {
    return $this->behavior->renderTemplate('queryChildFindOneBySlug', array(
      'objectClassname' => $this->builder->getStubObjectBuilder($this->table)->getClassname(),
      'defaultCulture'        => $this->behavior->getDefaultCulture(),
      'cultureColumn'         => $this->behavior->getCultureColumn()->getPhpName(),
      'slugColumn'         => $this->behavior->getSlugColumn()->getPhpName(),
    ));
  }
}