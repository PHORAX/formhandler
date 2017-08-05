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
 * Validates that an uploaded file has a minimum file size
 */
class FileMinSize extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['minSize'];
    }

    public function check()
    {
        $checkFailed = '';
        $minSize = $this->utilityFuncs->getSingle($this->settings['params'], 'minSize');
        foreach ($_FILES as $sthg => &$files) {
            if (!is_array($files['name'][$this->formFieldName])) {
                $files['name'][$this->formFieldName] = [$files['name'][$this->formFieldName]];
            }
            if (empty($files['name'][$this->formFieldName][0])) {
                $files['name'][$this->formFieldName] = [];
            }

            if (count($files['name'][$this->formFieldName]) > 0 && $minSize) {
                if (!is_array($files['size'][$this->formFieldName])) {
                    $files['size'][$this->formFieldName] = [$files['size'][$this->formFieldName]];
                }
                foreach ($files['size'][$this->formFieldName] as $size) {
                    if ($size < $minSize) {
                        unset($files);
                        $checkFailed = $this->getCheckFailed();
                    }
                }
            }
        }
        return $checkFailed;
    }
}
