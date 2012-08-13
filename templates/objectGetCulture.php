
/**
 * Gets the culture for translations
 *
 * @return    string $culture Culture to use for the translation, e.g. 'fr_FR'
 */
public function getCulture()
{
  return $this->currentCulture;
}

/**
 * alias for @getCulture
 *
 * @return    string $culture Culture to use for the translation, e.g. 'fr_FR'
 */
public function getLocale()
{
  return $this->getCulture();
}
