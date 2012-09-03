

if ($this->isColumnModified(<?php echo $const ?>) && $this-><?php echo $columnGetter?>())
{
  $this-><?php echo $columnSetter?>($this->makeSlugUnique($this-><?php echo $columnGetter?>(), $this->getCulture()));
<?php if (permanent == 'true'):?>
}
elseif (!$this-><?php echo $columnGetter?>())
{
  $this-><?php echo $columnSetter?>()?>($this->createSlug());
}
<?php else:?>
}
else
{
  $this-><?php echo $columnSetter?>($this->createSlug($this->getCulture()));
}
<?php endif ?>