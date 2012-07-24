/**
* Find one object based on its slug
*
* @param     string $slug The value to use as filter.
* @param     string $culture Cu;ture to use, e.g. 'en_GB'
* @param     PropelPDO $con The optional connection object
*
* @return   <?php echo $objectClassname?> the result, formatted by the current formatter
*/

public function findOneBySlug($slug, $culture = '<?php echo $defaultCulture ?>', $con = null)
{
  return $this
    ->useI18nQuery($culture, '<?php echo $i18nRelationName?>')
      ->filterBy('<?php echo $cultureColumn ?>', $culture)
      ->filterBy('<?php echo $slugColumn?>', $slug)
    ->endUse()
  ->findOne($con);
}
