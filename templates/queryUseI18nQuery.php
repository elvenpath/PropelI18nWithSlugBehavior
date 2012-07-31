
/**
 * Use the I18n relation query object
 *
 * @see       useQuery()
 *
 * @param     string $culture Culture to use for the join condition, e.g. 'fr_FR'
 * @param     string $relationAlias optional alias for the relation
 * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'. Defaults to left join.
 *
 * @return    <?php echo $queryClass ?> A secondary query class using the current class as primary query
 */
public function useI18nQuery($culture = null, $relationAlias = null, $joinType = Criteria::LEFT_JOIN)
{
  $relationAlias =  $relationAlias ? $relationAlias : '<?php echo $i18nRelationName ?>';

	return $this
		->joinI18n($culture, $relationAlias, $joinType)
		->useQuery($relationAlias, '<?php echo $namespacedQueryClass ?>');
}

