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
 * Validates that an uploaded file via specified field matches one of the given file types
 */
class FileAllowedTypes extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['allowedTypes'];
    }

    public function check()
    {
        $checkFailed = '';
        $allowed = $this->utilityFuncs->getSingle($this->settings['params'], 'allowedTypes');
        foreach ($_FILES as $sthg => &$files) {
            if (!is_array($files['name'][$this->formFieldName])) {
                $files['name'][$this->formFieldName] = [$files['name'][$this->formFieldName]];
            }
            foreach ($files['name'][$this->formFieldName] as $fileName) {
                if (strlen($fileName) > 0) {
                    if ($allowed) {
                        $types = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $allowed);
                        $fileext = substr($fileName, strrpos($fileName, '.') + 1);
                        $fileext = strtolower($fileext);
                        if (!in_array($fileext, $types)) {
                            unset($files);
                            $checkFailed = $this->getCheckFailed();
                        }
                    }
                }
            }
        }
        return $checkFailed;
    }
}
