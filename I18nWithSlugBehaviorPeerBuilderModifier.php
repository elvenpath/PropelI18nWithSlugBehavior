<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * Allows translation of text columns through transparent one-to-many relationship.
 * Modifier for the peer builder.
 *
 * @author     Vlad Jula-Nedelcu <vlad.nedelcu@mfq.ro>
 */
class I18nWithSlugBehaviorPeerBuilderModifier
{
	protected $behavior;

	public function __construct(I18nWithSlugBehavior $behavior)
	{
		$this->behavior = $behavior;
	}

	public function staticConstants()
	{
		return "
/**
 * The default culture to use for translations
 * @var        string
 */
const DEFAULT_CULTURE = '{$this->behavior->getDefaultCulture()}';";
	}
}