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
 * Validates that a specified field's value is a valid time
 */
class Time extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['pattern'];
    }

    public function check()
    {
        $checkFailed = '';

        if (isset($this->gp[$this->formFieldName]) && strlen(trim($this->gp[$this->formFieldName])) > 0) {
            $pattern = $this->utilityFuncs->getSingle($this->settings['params'], 'pattern');
            preg_match('/^[h|m]*(.)[h|m]*/i', $pattern, $res);
            $sep = $res[1];
            $timeCheck = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sep, $this->gp[$this->formFieldName]);
            if (is_array($timeCheck)) {
                $hours = $timeCheck[0];
                if (!is_numeric($hours) || $hours < 0 || $hours > 23) {
                    $checkFailed = $this->getCheckFailed();
                }
                $minutes = $timeCheck[1];
                if (!is_numeric($minutes) || $minutes < 0 || $minutes > 59) {
                    $checkFailed = $this->getCheckFailed();
                }
            }
        }
        return $checkFailed;
    }
}
