/**
* Wrap the setter for slug value
*
* @param   string
* @return  <?php echo $objectClassname ?> The current object (for fluent API support)
*/


public function setSlug($slug)
{
  return $this->getCurrentTranslation()->set<?php echo $slugColumnName?>($slug);
}
