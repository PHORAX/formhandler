<?php
namespace Typoheads\Formhandler\Controller;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The configuration of the Formhandler
 */
class Configuration implements \ArrayAccess
{

    /**
     * The package key
     *
     * @var string
     */
    const PACKAGE_KEY = 'Formhandler';

    /**
     * The TS setup
     *
     * @access protected
     * @var array
     */
    protected $setup;

    /**
     * The constructor reading the TS setup into the according attribute
     *
     * @return void
     */
    public function __construct()
    {
        if (TYPO3_MODE === 'FE') {
            $this->globals = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\Globals::class);
            $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
            $this->setup = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->getPrefixedPackageKey() . '.'];
            if (!is_array($this->setup)) {
                $this->utilityFuncs->throwException('missing_config');
            }
            if (is_array($this->globals->getOverrideSettings())) {
                $this->setup = $this->utilityFuncs->mergeConfiguration($this->setup, $this->globals->getOverrideSettings());
            }
        }
    }

    /**
     * Merges the values of $setup with plugin.[xxx].settings
     *
     * @param array $setup
     * @return void
     */
    public function merge($setup)
    {
        if (isset($setup) && is_array($setup)) {
            $settings = $this->setup['settings.'];
            $settings = $this->utilityFuncs->mergeConfiguration($settings, $setup);
            $this->setup['settings.'] = $settings;
        }
    }

    public function offsetGet($offset)
    {
        return $this->setup['settings.'][$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->setup['settings.'][$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->setup['settings.'][$offset]);
    }

    public function offsetUnset($offset)
    {
        $this->setup['settings.'][$offset] = null;
    }

    /**
     * Returns the TS settings for formhandler.
     *
     * @return array The settings
     */
    public function getSettings()
    {
        return $this->setup['settings.'];
    }

    /**
     * Returns the sources config for formhandler
     *
     * @return array The config
     */
    public function getSourcesConfiguration()
    {
        return $this->setup['sources.'];
    }

    /**
     * Returns the package key
     *
     * @return string
     */
    public function getPackageKey()
    {
        return self::PACKAGE_KEY;
    }

    /**
     * Returns the package key in lower case
     *
     * @return string
     */
    public function getPackageKeyLowercase()
    {
        return strtolower(self::PACKAGE_KEY);
    }

    /**
     * Returns the prefixed package key
     *
     * @return string
     */
    public function getPrefixedPackageKey()
    {
        return 'Tx_' . self::PACKAGE_KEY;
    }

    /**
     * Returns the prefixed package key in lower case
     *
     * @return string
     */
    public function getPrefixedPackageKeyLowercase()
    {
        return strtolower('Tx_' . self::PACKAGE_KEY);
    }
}
