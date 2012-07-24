/**
* Create a unique slug based on the object
*
* @return string The object slug
*/

protected function createSlug($culture = '<?php $defaultCulture ?>')
{
  $slug = $this->createRawSlug();
  $slug = $this->limitSlugSize($slug);
  $slug = $this->makeSlugUnique($slug, $culture);

  return $slug;
}

/**
* Create the slug from the appropriate columns
*
* @return string
*/

protected function createRawSlug()
{
  <?php if ($pattern):?>
  <?php echo "return '" . str_replace(array('{', '}'), array('\' . $this->cleanupSlugPart($this->get', '()) . \''), $pattern). "';";?>
  <?php else:?>
    return $this->cleanupSlugPart($this->__toString());
  <?php endif ?>
}

/**
* Cleanup a string to make a slug of it
* Removes special characters, replaces blanks with a separator, and trim it
*
* @param     string $text      the text to slugify
* @param     string $separator the separator used by slug
* @return    string             the slugified text
*/

protected static function cleanupSlugPart($slug, $replacement = '<?php echo $replacement?>')
{
  // transliterate
  if (function_exists('iconv'))
{
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
  }

  // lowercase
  if (function_exists('mb_strtolower'))
  {
    $slug = mb_strtolower($slug);
  }
  else
  {
    $slug = strtolower($slug);
  }

  // remove accents resulting from OSX's iconv
  $slug = str_replace(array('\'', '`', '^'), '', $slug);

  // replace non letter or digits with separator
  $slug = preg_replace('<?php echo $replace_pattern?>>', $replacement, $slug);

  // trim
  $slug = trim($slug, $replacement);

  if (empty($slug))
  {
    return 'n-a';
  }

  return $slug;
}

/**
* Make sure the slug is short enough to accomodate the column size
*
* @param	string $slug			the slug to check
*
* @return string						the truncated slug
*/
protected static function limitSlugSize($slug, $incrementReservedSpace = 3)
{
  // check length, as suffix could put it over maximum
  if (strlen($slug) > (<?php echo $size?> - $incrementReservedSpace))
  {
    $slug = substr($slug, 0, <?php echo $size?> - $incrementReservedSpace);
  }

  return $slug;
}

/**
* Get the slug, ensuring its uniqueness
*
* @param	string $slug			the slug to check
* @param	string $separator the separator used by slug
* @return string						the unique slug
*/

protected function makeSlugUnique($slug, $culture = '<?php echo $defaultCulture ?>', $separator = '<?php echo $separator?>', $increment = 0)
{
  $slug2 = empty($increment) ? $slug : $slug . $separator . $increment;
  $slugAlreadyExists = <?php echo $i18nQueryName?>::create()->filterBy<?php echo $cultureColumnName?>($culture)->filterBySlug($slug2)->prune($this)
  // watch out: some of the columns may be hidden by the soft_delete behavior
  <?php if ($softDeleteBehaviour)?>->includeDeleted()
  ->count();

  if ($slugAlreadyExists)
  {
    return $this->makeSlugUnique($slug, $culture, $separator, ++$increment);
  }
  else
  {
    return $slug2;
  }
}