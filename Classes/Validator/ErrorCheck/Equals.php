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
 * Validates that a specified field equals a specified word
 */
class Equals extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['word'];
    }

    public function check()
    {
        $checkFailed = '';
        $formValue = trim($this->gp[$this->formFieldName]);

        if (isset($this->gp[$this->formFieldName]) && strlen(trim($this->gp[$this->formFieldName])) > 0) {
            $checkValue = $this->utilityFuncs->getSingle($this->settings['params'], 'word');
            if (strcasecmp($formValue, $checkValue)) {

                //remove userfunc settings
                unset($this->settings['params']['word.']);
                $checkFailed = $this->getCheckFailed();
            }
        }
        return $checkFailed;
    }
}
