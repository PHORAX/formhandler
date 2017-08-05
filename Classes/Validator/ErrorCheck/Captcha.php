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
 * Validates that a specified field's value matches the generated word of the extension "captcha"
 */
class Captcha extends AbstractErrorCheck
{
    public function check()
    {
        $checkFailed = '';

        // get captcha string
        session_start();

        $captchaSolved = \ThinkopenAt\Captcha\Utility::checkCaptcha($this->gp[$this->formFieldName]);
        if (!$captchaSolved) {
            $checkFailed = $this->getCheckFailed();
        }

        return $checkFailed;
    }
}
