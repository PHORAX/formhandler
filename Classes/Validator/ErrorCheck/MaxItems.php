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
 * Validates that a specified field is an array and has less than or exactly a specified amount of items
 */
class MaxItems extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['value'];
    }

    public function check()
    {
        $checkFailed = '';

        if (isset($this->gp[$this->formFieldName])) {
            $value = $this->utilityFuncs->getSingle($this->settings['params'], 'value');
            $removeEmptyValues = $this->utilityFuncs->getSingle($this->settings['params'], 'removeEmptyValues');
            if (is_array($this->gp[$this->formFieldName])) {
                $valuesArray = $this->gp[$this->formFieldName];
                if (intval($removeEmptyValues) === 1) {
                    foreach ($valuesArray as $key => $fieldName) {
                        if (empty($fieldName)) {
                            unset($valuesArray[$key]);
                        }
                    }
                }
                if (count($valuesArray) > $value) {
                    $checkFailed = $this->getCheckFailed();
                }
            } else {
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
