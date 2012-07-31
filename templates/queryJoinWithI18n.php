
/**
 * Adds a JOIN clause to the query and hydrates the related I18n object.
 * Shortcut for $c->joinI18n($culture)->with()
 *
 * @param     string $culture Culture to use for the join condition, e.g. 'fr_FR'
 * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
 *
 * @return    <?php echo $queryClass ?> The current query, for fluid interface
 */
public function joinWithI18n($culture = null, $relationAlias = '<?php echo $i18nRelationName ?>', $joinType = Criteria::LEFT_JOIN)
{
  $this->joinI18n($culture, $relationAlias, $joinType)->with($relationAlias);

	$this->with[$relationAlias]->setIsWithOneToMany(false);

	return $this;
}
