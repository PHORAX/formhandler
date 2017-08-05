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
 * Validates that a specified field is filled out
 */
class Required extends AbstractErrorCheck
{
    public function check()
    {
        $checkFailed = '';
        if (is_array($this->gp[$this->formFieldName])) {
            if (empty($this->gp[$this->formFieldName])) {
                $checkFailed = $this->getCheckFailed();
            }
        } elseif (!isset($this->gp[$this->formFieldName]) || strlen(trim($this->gp[$this->formFieldName])) == 0) {
            $checkFailed = $this->getCheckFailed();
        }
        return $checkFailed;
    }
}
