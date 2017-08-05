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
 * Validates that a specified field value is a valid URL.
 */
class Url extends AbstractErrorCheck
{

    /**
     * Validates that a specified field has valid url syntax.
     *
     * @param array &$check The TypoScript settings for this error check
     * @param string $name The field name
     * @param array &$gp The current GET/POST parameters
     * @return string The error string
     */
    public function check()
    {
        $checkFailed = '';

        if (isset($this->gp[$this->formFieldName]) && strlen(trim($this->gp[$this->formFieldName])) > 0) {
            $valid = \TYPO3\CMS\Core\Utility\GeneralUtility::isValidUrl($this->gp[$this->formFieldName]);
            if (!$valid) {
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
