
/**
 * Adds a JOIN clause to the query using the i18n relation
 *
 * @param     string $culture Culture to use for the join condition, e.g. 'fr_FR'
 * @param     string $relationAlias optional alias for the relation
 * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
 *
 * @return    <?php echo $queryClass ?> The current query, for fluid interface
 */
public function joinI18n($culture = null, $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
{
  if (is_null($culture))
  {
    if (sfContext::hasInstance())
    {
      $culture = sfContext::getInstance()->getUser()->getCulture();
    }
    else
    {
      $culture = '<?php echo $defaultCulture ?>';
    }
  }

	$relationName = $relationAlias ? $relationAlias : '<?php echo $i18nRelationName ?>';

	return $this->join<?php echo $i18nRelationName ?>($relationAlias, $joinType)
		->addJoinCondition($relationName, $relationName . '.<?php echo $cultureColumn ?> = ?', $culture);
}
