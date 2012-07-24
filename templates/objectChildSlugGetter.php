/**
* Wrap the getter for slug
*
* @return  string
*/
public function getSlug()
{
  return $this->get<?php echo $slugColumnName?>();
}
