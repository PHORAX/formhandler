<?php

namespace Typoheads\Formhandler\Validator\ErrorCheck;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Validates that a specified field's value matches the generated word of the extension "sr_freecap"
 */
class BwCaptcha extends AbstractErrorCheck
{
    public function check()
    {
        $checkFailed = '';
        if (ExtensionManagementUtility::isLoaded('bw_captcha')) {
            $cacheIdentifier = $GLOBALS['TSFE']->fe_user->getKey('ses', 'captchaId');

            if (!$cacheIdentifier) {
                return $this->getCheckFailed();
            }

            // get captcha secret from cache and compare
            $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('bwcaptcha');
            $phrase = $cache->get($cacheIdentifier);

            if (!$phrase || !is_string($this->gp[$this->formFieldName]) || $phrase !== $this->gp[$this->formFieldName]) {
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
