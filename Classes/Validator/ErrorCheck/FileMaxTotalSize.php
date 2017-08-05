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

class FileMaxTotalSize extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['maxTotalSize'];
    }

    public function check()
    {
        $checkFailed = '';
        $maxSize = $this->utilityFuncs->getSingle($this->settings['params'], 'maxTotalSize');
        $size = 0;

        // first we check earlier uploaded files
        $olderFiles = $this->globals->getSession()->get('files');
        foreach ((array)$olderFiles[$this->formFieldName] as $olderFile) {
            $size += intval($olderFile['size']);
        }

        // last we check currently uploaded file
        foreach ($_FILES as $sthg => &$files) {
            if (!is_array($files['name'][$this->formFieldName])) {
                $files['name'][$this->formFieldName] = [$files['name'][$this->formFieldName]];
            }
            if (strlen($files['name'][$this->formFieldName][0]) > 0 && $maxSize) {
                if (!is_array($files['size'][$this->formFieldName])) {
                    $files['size'][$this->formFieldName] = [$files['size'][$this->formFieldName]];
                }
                foreach ($files['size'][$this->formFieldName] as $fileSize) {
                    $size += $fileSize;
                }
                if ($size > $maxSize) {
                    unset($files);
                    $checkFailed = $this->getCheckFailed();
                }
            }
        }
        return $checkFailed;
    }
}
