<?php
namespace Typoheads\Formhandler\Validator\ErrorCheck;

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
 * Validates that a specified field's value matches the generated word of the extension "jm_recaptcha"
 */
class JmRecaptcha extends AbstractErrorCheck
{
    public function check()
    {
        $checkFailed = '';
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('jm_recaptcha')) {
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('jm_recaptcha') . 'class.tx_jmrecaptcha.php');
            $this->recaptcha = new \tx_jmrecaptcha();
            $status = $this->recaptcha->validateReCaptcha();
            if (!$status['verified']) {
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
