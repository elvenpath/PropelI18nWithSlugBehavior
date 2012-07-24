
/**
 * Returns the current translation for a given culture
 *
 * @param     string $culture Culture to use for the translation, e.g. 'fr_FR'
 * @param     PropelPDO $con an optional connection object
 *
 * @return <?php echo $i18nTablePhpName ?>
 */
public function getTranslation($culture = '<?php echo $defaultCulture ?>', PropelPDO $con = null)
{
	if (!isset($this->currentTranslations[$culture])) {
		if (null !== $this-><?php echo $i18nListVariable ?>) {
			foreach ($this-><?php echo $i18nListVariable ?> as $translation) {
				if ($translation->get<?php echo $cultureColumnName ?>() == $culture) {
					$this->currentTranslations[$culture] = $translation;
					return $translation;
				}
			}
		}
		if ($this->isNew()) {
			$translation = new <?php echo $i18nTablePhpName ?>();
			$translation->set<?php echo $cultureColumnName ?>($culture);
		} else {
			$translation = <?php echo $i18nQueryName ?>::create()
				->filterByPrimaryKey(array($this->getPrimaryKey(), $culture))
				->findOneOrCreate($con);
			$this->currentTranslations[$culture] = $translation;
		}
		$this->add<?php echo $i18nSetterMethod ?>($translation);
	}

	return $this->currentTranslations[$culture];
}
