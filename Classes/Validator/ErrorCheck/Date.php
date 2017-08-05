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
 * Validates that a specified field's value is a valid date
 */
class Date extends AbstractErrorCheck
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
            try {
                \DateTime::createFromFormat($pattern, $this->gp[$this->formFieldName]);
                $status = \DateTime::getLastErrors();
                if ((isset($status['warning_count']) && intval($status['warning_count']) > 0) ||
                    (isset($status['error_count']) && intval($status['error_count']) > 0)) {
                    $checkFailed = $this->getCheckFailed();
                    $this->utilityFuncs->debugMessage('Result:', [], 2, $status);
                }
            } catch (\Exception $e) {
                $checkFailed = $this->getCheckFailed();
                $this->utilityFuncs->debugMessage($e->getMessage());
            }
        }
        return $checkFailed;
    }
}
