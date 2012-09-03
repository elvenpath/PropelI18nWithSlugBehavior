<?php /** @var $columns Column[] */ ?>
<?php /** @var $table Table */ ?>
<?php foreach ($columns as $column):?>
<?php if (!$table->hasColumn($column->getName())): ?>

/**
* @see <?php echo $callerClassName ?>::get<?php echo $column->getPhpName() ?>
*
* @return string
*/
protected function get<?php echo $column->getPhpName() ?>()
{
return $this->get<?php echo $callerClassName ?>()->get<?php echo $column->getPhpName() ?>();
}

  <?php endif ?>
<?php endforeach ?>