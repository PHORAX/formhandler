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
 * Validates that an uploaded file has a maximum file size
 */
class FileMaxSize extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['maxSize'];
    }

    public function check()
    {
        $checkFailed = '';
        $maxSize = intval($this->utilityFuncs->getSingle($this->settings['params'], 'maxSize'));
        $phpIniUploadMaxFileSize = $this->utilityFuncs->convertBytes(ini_get('upload_max_filesize'));
        if ($maxSize > $phpIniUploadMaxFileSize) {
            $this->utilityFuncs->throwException('error_check_filemaxsize', \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($maxSize, ' Bytes| KB| MB| GB'), $this->formFieldName, \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($phpIniUploadMaxFileSize, ' Bytes| KB| MB| GB'));
        }
        foreach ($_FILES as $sthg => &$files) {
            if (!is_array($files['name'][$this->formFieldName])) {
                $files['name'][$this->formFieldName] = [$files['name'][$this->formFieldName]];
            }
            if (strlen($files['name'][$this->formFieldName][0]) > 0 && $maxSize) {
                if (!is_array($files['size'][$this->formFieldName])) {
                    $files['size'][$this->formFieldName] = [$files['size'][$this->formFieldName]];
                }
                foreach ($files['size'][$this->formFieldName] as $size) {
                    if ($size > $maxSize) {
                        unset($files);
                        $checkFailed = $this->getCheckFailed();
                    }
                }
            }
        }
        return $checkFailed;
    }
}
