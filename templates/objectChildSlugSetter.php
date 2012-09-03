
/**
* Wrap the setter for slug value
*
* @param   string
* @return  <?php echo $objectClassname ?> The current object (for fluent API support)
*/
public function setSlug($slug)
{
  return $this->set<?php echo $slugColumnName?>($slug);
}

