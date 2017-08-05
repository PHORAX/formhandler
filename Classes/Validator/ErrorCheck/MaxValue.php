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
 * Validates that a specified field is an integer and lower than or equal a specified value
 */
class MaxValue extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['value'];
    }

    public function check()
    {
        $checkFailed = '';
        $max = floatval(str_replace(',', '.', $this->utilityFuncs->getSingle($this->settings['params'], 'value')));
        if (isset($this->gp[$this->formFieldName]) && strlen($this->gp[$this->formFieldName]) > 0) {
            $valueToCheck = str_replace(',', '.', $this->gp[$this->formFieldName]);
            if (!is_numeric($valueToCheck)) {
                $checkFailed = $this->getCheckFailed();
            } else {
                $valueToCheck = floatval($valueToCheck);
                if ($valueToCheck > $max) {
                    $checkFailed = $this->getCheckFailed();
                }
            }
        }

        return $checkFailed;
    }
}
