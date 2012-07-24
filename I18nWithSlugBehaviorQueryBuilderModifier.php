<?php
/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the query builder.
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 */
class I18nWithSlugBehaviorQueryBuilderModifier
{
	protected $behavior, $table, $builder;

	public function __construct(I18nWithSlugBehavior $behavior)
	{
		$this->behavior = $behavior;
		$this->table = $behavior->getTable();
	}

	public function queryMethods($builder)
	{
		$this->builder = $builder;

		$script = '';
		$script .= $this->addJoinI18n();
		$script .= $this->addJoinWithI18n();
		$script .= $this->addUseI18nQuery();
    $script .= $this->addFilterBySlug();
    $script .= $this->addFindOneBySlug();

		return $script;
	}

	protected function addJoinI18n()
	{
		$fk = $this->behavior->getI18nForeignKey();
		return $this->behavior->renderTemplate('queryJoinI18n', array(
			'queryClass'       => $this->builder->getStubQueryBuilder()->getClassname(),
			'defaultCulture'    => $this->behavior->getDefaultCulture(),
			'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
			'cultureColumn'     => $this->behavior->getCultureColumn()->getPhpName(),
		));
	}

	protected function addJoinWithI18n()
	{
		$fk = $this->behavior->getI18nForeignKey();
		return $this->behavior->renderTemplate('queryJoinWithI18n', array(
			'queryClass'       => $this->builder->getStubQueryBuilder()->getClassname(),
			'defaultCulture'    => $this->behavior->getDefaultCulture(),
			'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($fk),
		));
	}

	protected function addUseI18nQuery()
	{
		$i18nTable = $this->behavior->getI18nTable();
		$fk = $this->behavior->getI18nForeignKey();
		return $this->behavior->renderTemplate('queryUseI18nQuery', array(
			'queryClass'           => $this->builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
			'namespacedQueryClass' => $this->builder->getNewStubQueryBuilder($i18nTable)->getFullyQualifiedClassname(),
			'defaultCulture'        => $this->behavior->getDefaultCulture(),
			'i18nRelationName'     => $this->builder->getRefFKPhpNameAffix($fk),
			'cultureColumn'         => $this->behavior->getCultureColumn()->getPhpName(),
		));
	}

  protected function addFilterBySlug()
  {
    $i18nTable = $this->behavior->getI18nTable();
    return $this->behavior->renderTemplate('queryFilterBySlug', array(
      'queryClass'           => $this->builder->getNewStubQueryBuilder($i18nTable)->getClassname(),
      'defaultCulture'        => $this->behavior->getDefaultCulture(),
      'cultureColumn'         => $this->behavior->getCultureColumn()->getPhpName(),
      'slugColumn'         => $this->behavior->getSlugColumn()->getPhpName(),
      'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($this->behavior->getI18nForeignKey()),
    ));
  }

  protected function addFindOneBySlug()
  {
    $i18nTable = $this->behavior->getI18nTable();
    return $this->behavior->renderTemplate('queryFindOneBySlug', array(
      'objectClassname' => $this->builder->getStubObjectBuilder($this->table)->getClassname(),
      'defaultCulture'        => $this->behavior->getDefaultCulture(),
      'cultureColumn'         => $this->behavior->getCultureColumn()->getPhpName(),
      'slugColumn'         => $this->behavior->getSlugColumn()->getPhpName(),
      'i18nRelationName' => $this->builder->getRefFKPhpNameAffix($this->behavior->getI18nForeignKey()),
    ));
  }
}