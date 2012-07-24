
/**
 * Gets the culture for translations.
 * Alias for getCulture(), for BC purpose.
 *
 * @return    string $culture Culture to use for the translation, e.g. 'fr_FR'
 */
public function get<?php echo $alias ?>()
{
	return $this->getCulture();
}
