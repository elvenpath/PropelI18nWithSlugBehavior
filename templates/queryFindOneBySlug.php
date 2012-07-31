
/**
* Find one object based on its slug
*
* @param     string $slug The value to use as filter.
* @param     string $culture Culture to use, e.g. 'en_GB'
* @param     PropelPDO $con The optional connection object
*
* @return   <?php echo $objectClassname?> the result, formatted by the current formatter
*/
public function findOneBySlug($slug, $culture = '<?php echo $defaultCulture ?>', $con = null)
{
  $this->joinI18n($culture, '<?php echo $i18nRelationName?>')
    ->where('<?php echo $i18nRelationName?>.<?php echo $slugColumn?> = ?', $slug);

  return $this->findOne($con);
}
