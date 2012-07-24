/**
* Wrap the getter for slug value
*
* @return  string
*/

public function getSlug()
{
  return $this->getCurrentTranslation()->get<?php echo $slugColumnName?>();
}
