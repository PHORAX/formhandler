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
 * Validates that a specified field is a string and shorter a specified count of characters
 */
class MaxLength extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['value'];
    }

    public function check()
    {
        $checkFailed = '';
        $max = $this->utilityFuncs->getSingle($this->settings['params'], 'value');
        if (isset($this->gp[$this->formFieldName]) &&
            mb_strlen(trim($this->gp[$this->formFieldName]), $GLOBALS['TSFE']->renderCharset) > 0 &&
            intval($max) > 0 &&
            mb_strlen(trim($this->gp[$this->formFieldName]), $GLOBALS['TSFE']->renderCharset) > $max
        ) {
            $checkFailed = $this->getCheckFailed();
        }
        return $checkFailed;
    }
}
