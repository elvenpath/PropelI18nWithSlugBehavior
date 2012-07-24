
/**
 * Sets the culture for translations
 *
 * @param     string $culture Culture to use for the translation, e.g. 'fr_FR'
 * @return    <?php echo $objectClassname ?> The current object (for fluent API support)
 */
public function setCulture($culture = '<?php echo $defaultCulture ?>')
{
	$this->currentCulture = $culture;

	return $this;
}
