
/**
 * Sets the culture for translations.
 * Alias for setCulture(), for BC purpose.
 *
 * @param     string $culture Culture to use for the translation, e.g. 'fr_FR'
 *
 * @return    <?php echo $objectClassname ?> The current object (for fluent API support)
 */
public function set<?php echo $alias ?>($culture = '<?php echo $defaultCulture ?>')
{
	return $this->setCulture($culture);
}
