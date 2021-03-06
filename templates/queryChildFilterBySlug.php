
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
  return $this->filterBy('<?php echo $cultureColumn ?>', $culture)->filterBy('<?php echo $slugColumn?>', $slug);
}

