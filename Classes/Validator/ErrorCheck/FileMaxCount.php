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
 * Validates that up to x files get uploaded via the specified upload field.
 */
class FileMaxCount extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['maxCount'];
    }

    public function check()
    {
        $checkFailed = '';

        $files = $this->globals->getSession()->get('files');
        $settings = $this->globals->getSession()->get('settings');
        $currentStep = intval($this->globals->getSession()->get('currentStep'));
        $lastStep = intval($this->globals->getSession()->get('lastStep'));
        $maxCount = intval($this->utilityFuncs->getSingle($this->settings['params'], 'maxCount'));

        $uploadedFilesWithSameNameAction = $this->utilityFuncs->getSingle($settings['files.'], 'uploadedFilesWithSameName');
        if (!$uploadedFilesWithSameNameAction) {
            $uploadedFilesWithSameNameAction = 'ignore';
        }
        if (is_array($files[$this->formFieldName]) &&
            count($files[$this->formFieldName]) >= $maxCount &&
            $currentStep === $lastStep
        ) {
            $found = false;
            foreach ($_FILES as $idx => $info) {
                if (isset($info['name'][$this->formFieldName])) {
                    if (!is_array($info['name'][$this->formFieldName])) {
                        $info['name'][$this->formFieldName] = [$info['name'][$this->formFieldName]];
                    }
                    if (strlen($info['name'][$this->formFieldName][0]) > 0) {
                        $found = true;
                    }
                }
            }
            if ($found) {
                foreach ($info['name'][$this->formFieldName] as $newFileName) {
                    $exists = false;
                    foreach ($files[$this->formFieldName] as $fileInfo) {
                        if ($fileInfo['name'] === $newFileName) {
                            $exists = true;
                        }
                    }
                    if (!$exists) {
                        $checkFailed = $this->getCheckFailed();
                    } elseif ($uploadedFilesWithSameNameAction === 'append') {
                        $checkFailed = $this->getCheckFailed();
                    }
                }
            }
        } else {
            if (!is_array($files[$this->formFieldName])) {
                $files[$this->formFieldName] = [];
            }
            foreach ($_FILES as $idx => $info) {
                if (!is_array($info['name'][$this->formFieldName])) {
                    $info['name'][$this->formFieldName] = [$info['name'][$this->formFieldName]];
                }
                if (strlen($info['name'][$this->formFieldName][0]) > 0 && count($info['name'][$this->formFieldName]) + count($files[$this->formFieldName]) > $maxCount) {
                    $checkFailed = $this->getCheckFailed();
                }
            }
        }
        return $checkFailed;
    }
}
