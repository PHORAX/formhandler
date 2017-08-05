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
 * Validates that a person is older than a specified amount of years by converting a specified date field's value to a timestamp.
 */
class IsYoungerThan extends IsOlderThan
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['dateFormat', 'years'];
    }

    public function check()
    {
        $checkFailed = '';

        if (isset($this->gp[$this->formFieldName]) && strlen(trim($this->gp[$this->formFieldName])) > 0) {
            $date = $this->gp[$this->formFieldName];
            $dateFormat = $this->utilityFuncs->getSingle($this->settings['params'], 'dateFormat');
            $mandatoryYears = intval($this->utilityFuncs->getSingle($this->settings['params'], 'years'));
            $timestamp = $this->utilityFuncs->dateToTimestamp($date, $dateFormat);
            $years = $this->getDateDifference($timestamp);
            if ($years >= $mandatoryYears) {
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
