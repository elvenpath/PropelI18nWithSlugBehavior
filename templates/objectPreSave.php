

if ($this->isColumnModified(<?php echo $const ?>) && $this-><?php echo $columnGetter?>())
{
  $this-><?php echo $columnSetter?>($this->makeSlugUnique($this-><?php echo $columnGetter?>(), '<?php echo $defaultCulture?>'));
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
  $this-><?php echo $columnSetter?>($this->createSlug());
}
<?php endif ?>