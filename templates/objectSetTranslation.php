
/**
 * Sets the translation for a given culture
 *
 * @param     <?php echo $i18nTablePhpName ?> $translation The translation object
 * @param     string $culture Culture to use for the translation, e.g. 'fr_FR'
 * @return    <?php echo $objectClassname ?> The current object (for fluent API support)
 */
public function setTranslation($translation, $culture = '<?php echo $defaultCulture ?>')
{
	$translation->set<?php echo $cultureColumnName ?>($culture);
	$this->add<?php echo $i18nTablePhpName ?>($translation);
	$this->currentTranslations[$culture] = $translation;

	return $this;
}
