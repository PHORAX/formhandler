<?php
namespace Typoheads\Formhandler\Validator\ErrorCheck;

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
 * Validates that a specified field contains only the specified words/characters
 */
class ContainsOnly extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['words'];
    }

    public function check()
    {
        $checkFailed = '';
        $formValue = trim($this->gp[$this->formFieldName]);

        if (strlen($formValue) > 0) {
            $checkValue = $this->utilityFuncs->getSingle($this->settings['params'], 'words');
            if (!is_array($checkValue)) {
                $checkValue = GeneralUtility::trimExplode(',', $checkValue);
            }
            $error = false;
            $array = preg_split('//', $formValue, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($array as $idx => $char) {
                if (!in_array($char, $checkValue)) {
                    $error = true;
                }
            }
            if ($error) {

                //remove userfunc settings and only store comma seperated words
                $this->settings['params']['words'] = implode(',', $checkValue);
                unset($this->settings['params']['words.']);
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
