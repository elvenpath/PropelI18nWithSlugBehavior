
/**
 * Remove the translation for a given culture
 *
 * @param     string $culture Culture to use for the translation, e.g. 'fr_FR'
 * @param     PropelPDO $con an optional connection object
 *
 * @return    <?php echo $objectClassname ?> The current object (for fluent API support)
 */
public function removeTranslation($culture = '<?php echo $defaultCulture ?>', PropelPDO $con = null)
{
	if (!$this->isNew()) {
		<?php echo $i18nQueryName ?>::create()
			->filterByPrimaryKey(array($this->getPrimaryKey(), $culture))
			->delete($con);
	}
	if (isset($this->currentTranslations[$culture])) {
		unset($this->currentTranslations[$culture]);
	}
	foreach ($this-><?php echo $i18nCollection ?> as $key => $translation) {
		if ($translation->get<?php echo $cultureColumnName ?>() == $culture) {
			unset($this-><?php echo $i18nCollection ?>[$key]);
			break;
		}
	}

	return $this;
}
