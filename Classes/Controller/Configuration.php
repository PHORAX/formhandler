<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Utility\Globals;

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
     * @var array
     */
    protected array $setup = [];

    /**
     * @var Globals
     */
    private Globals $globals;

    /**
     * @var \Typoheads\Formhandler\Utility\GeneralUtility
     */
    private \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs;

    /**
     * The constructor reading the TS setup into the according attribute
     */
    public function __construct()
    {
        if (!isset($GLOBALS['TYPO3_REQUEST']) || ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
            $this->globals = GeneralUtility::makeInstance(Globals::class);
            $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
            $this->setup = ($GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->getPrefixedPackageKeyLowercase() . '.'] ?? null);
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
     * @param array|null $setup
     */
    public function merge(?array $setup)
    {
        if (isset($setup) && is_array($setup)) {
            $settings = $this->setup['settings.'];
            $settings = $this->utilityFuncs->mergeConfiguration($settings, $setup);
            $this->setup['settings.'] = $settings;
        }
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->setup['settings.'][$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setup['settings.'][$offset] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->setup['settings.'][$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->setup['settings.'][$offset] = null;
    }

    /**
     * Returns the TS settings for formhandler.
     *
     * @return array The settings
     */
    public function getSettings(): array
    {
        return isset($this->setup['settings.']) ? (is_array($this->setup['settings.'])? $this->setup['settings.']: []) : [];
    }

    /**
     * Returns the sources config for formhandler
     *
     * @return array The config
     */
    public function getSourcesConfiguration(): array
    {
        return isset($this->setup['sources.']) ? (is_array($this->setup['sources.'])? $this->setup['sources.']: []) : [];
    }

    /**
     * Returns the package key
     *
     * @return string
     */
    public function getPackageKey(): string
    {
        return self::PACKAGE_KEY;
    }

    /**
     * Returns the package key in lower case
     *
     * @return string
     */
    public function getPackageKeyLowercase(): string
    {
        return strtolower($this->getPackageKey());
    }

    /**
     * Returns the prefixed package key
     *
     * @return string
     */
    public function getPrefixedPackageKey(): string
    {
        return 'Tx_' . self::PACKAGE_KEY . "_pi1";
    }

    /**
     * Returns the prefixed package key in lower case
     *
     * @return string
     */
    public function getPrefixedPackageKeyLowercase(): string
    {
        return strtolower($this->getPrefixedPackageKey());
    }
}
