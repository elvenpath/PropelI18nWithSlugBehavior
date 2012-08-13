<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the peer builder.
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 */
class I18nWithSlugBehaviorPeerBuilderModifier
{
  protected $behavior, $table, $builder;

  /**
   * @param I18nWithSlugBehavior $behavior
   */
  public function __construct(I18nWithSlugBehavior $behavior)
	{
		$this->behavior = $behavior;
	}

  /**
   * @param $builder
   *
   * @return string
   */
  public function staticConstants($builder)
	{
    return "
/**
 * The default culture to use for translations
 * @var        string
 */
const DEFAULT_CULTURE = '{$this->behavior->getDefaultCulture()}';";
	}


  /**
   * @param $builder
   *
   * @return string
   */
  public function staticMethods($builder)
  {
    /** @var $foreignKey ForeignKey */
    $foreignKey = $this->behavior->getI18nForeignKey();
    $refPhpName = $foreignKey->getRefPhpName() ? $foreignKey->getRefPhpName() : $this->behavior->getI18nTable()->getPhpName();
    $join = in_array($this->behavior->getBuildProperty('propel.useLeftJoinsInDoJoinMethods'), array(true, null), true) ? 'LEFT' : 'INNER';

    $behaviors = $this->behavior->getTable()->getBehaviors();
    $mixerHook = !isset($behaviors['symfony_behaviors']) ? '' : <<<EOF

  foreach (sfMixer::getCallables('Base{$this->behavior->getTable()->getPhpName()}:doSelectJoin:doSelectJoin') as \$sf_hook)
  {
    call_user_func(\$sf_hook, '{$this->behavior->getTable()->getPhpName()}', \$criteria, \$con);
  }

EOF;

    if ($this->behavior->getTable()->getChildrenColumn())
    {
      $newObject = "\$cls = {$this->behavior->getTable()->getPhpName()}Peer::getOMClass(\$row, 0, false)";
    }
    else
    {
      $newObject = "\$cls = {$this->behavior->getTable()->getPhpName()}Peer::getOMClass(false)";
    }

    return <<<EOF

/**
 * Returns the i18n model class name.
 *
 * @return string The i18n model class name
 */
static public function getI18nModel()
{
  return '{$this->behavior->getI18nTable()->getPhpName()}';
}

/**
 * Selects a collection of {@link {$this->behavior->getTable()->getPhpName()}} objects with a {@link {$this->behavior->getI18nTable()->getPhpName()}} translation populated.
 *
 * @param Criteria  \$criteria
 * @param string    \$culture
 * @param PropelPDO \$con
 * @param string    \$join_behavior
 *
 * @return array
 */
static public function doSelectWithI18n(Criteria \$criteria, \$culture = null, \$con = null, \$join_behavior = Criteria::{$join}_JOIN)
{
  \$criteria = clone \$criteria;

  if (null === \$culture)
  {
    \$culture = sfPropel::getDefaultCulture();
  }

  // Set the correct dbName if it has not been overridden
  if (\$criteria->getDbName() == Propel::getDefaultDB()) {
  	\$criteria->setDbName(self::DATABASE_NAME);
  }

  {$this->behavior->getTable()->getPhpName()}Peer::addSelectColumns(\$criteria);
  \$startcol = ({$this->behavior->getTable()->getPhpName()}Peer::NUM_COLUMNS - {$this->behavior->getTable()->getPhpName()}Peer::NUM_LAZY_LOAD_COLUMNS);
  {$this->behavior->getI18nTable()->getPhpName()}Peer::addSelectColumns(\$criteria);
  \$criteria->addJoin({$this->behavior->getLocalColumn()->getConstantName()}, {$this->behavior->getForeignColumn()->getConstantName()}, \$join_behavior);
  \$criteria->add({$this->behavior->getCultureColumn($this->behavior->getI18nTable())->getConstantName()}, \$culture);
{$mixerHook}
  \$stmt = {$builder->getBasePeerClassname()}::doSelect(\$criteria, \$con);
	\$results = array();

	while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
		\$key1 = {$this->behavior->getTable()->getPhpName()}Peer::getPrimaryKeyHashFromRow(\$row, 0);
		if (null !== (\$obj1 = {$this->behavior->getTable()->getPhpName()}Peer::getInstanceFromPool(\$key1))) {
			// We no longer rehydrate the object, since this can cause data loss.
  		// See http://propel.phpdb.org/trac/ticket/509
  		// \$obj1->hydrate(\$row, 0, true); // rehydrate
  	} else {
			{$newObject};
			\$obj1 = new \$cls();
			\$obj1->hydrate(\$row);
      {$this->behavior->getTable()->getPhpName()}Peer::addInstanceToPool(\$obj1, \$key1);
		} // if \$obj1 already loaded

		\$key2 = {$this->behavior->getI18nTable()->getPhpName()}Peer::getPrimaryKeyHashFromRow(\$row, \$startcol);
		if (\$key2 !== null) {
			\$obj2 = {$this->behavior->getI18nTable()->getPhpName()}Peer::getInstanceFromPool(\$key2);
			if (!\$obj2) {
				\$cls = {$this->behavior->getI18nTable()->getPhpName()}Peer::getOMClass(false);
				\$obj2 = new \$cls();
				\$obj2->hydrate(\$row, \$startcol);
				{$this->behavior->getI18nTable()->getPhpName()}Peer::addInstanceToPool(\$obj2, \$key2);
			} // if obj2 already loaded

      \$obj1->set{$refPhpName}ForCulture(\$obj2, \$culture);
		} // if joined row was not null

		\$results[] = \$obj1;
	}

	\$stmt->closeCursor();

	return \$results;
}

EOF;
  }

}