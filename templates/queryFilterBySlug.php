
/**
* Filter the query on the slug column
*
* @see       filterBy()
*
* @param     string $slug The value to use as filter.
* @return    <?php echo $queryClass ?> The current query, for fluid interface
*/
public function filterBySlug($slug, $culture = '<?php echo $defaultCulture ?>')
{
  $this->joinI18n($culture, '<?php echo $i18nRelationName?>')
    ->where('<?php echo $i18nRelationName?>.<?php echo $slugColumn?> = ?', $slug);

  return $this;
}

