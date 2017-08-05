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
 * Validates that a file gets uploaded via specified upload field
 */
class FileRequired extends AbstractErrorCheck
{
    public function check()
    {
        $checkFailed = '';
        $sessionFiles = $this->globals->getSession()->get('files');
        $found = false;
        foreach ($_FILES as $sthg => &$files) {
            if (!is_array($files['name'][$this->formFieldName])) {
                $files['name'][$this->formFieldName] = [$files['name'][$this->formFieldName]];
            }
            if (is_array($files['name'][$this->formFieldName]) && !empty($files['name'][$this->formFieldName][0])) {
                $found = true;
            }
        }
        if (!$found && count($sessionFiles[$this->formFieldName]) === 0) {
            $checkFailed = $this->getCheckFailed();
        }
        return $checkFailed;
    }
}
