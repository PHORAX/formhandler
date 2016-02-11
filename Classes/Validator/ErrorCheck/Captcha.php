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
 *
 * $Id$
 *                                                                        */

/**
 * Validates that a specified field's value matches the generated word of the extension "captcha"
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 * @package	Tx_Formhandler
 * @subpackage	ErrorChecks
 */
class Captcha extends AbstractErrorCheck {

	public function check() {
		$checkFailed = '';

		// get captcha string
		session_start();

		// make sure that an anticipated answer to the captcha actually exists
		if ( isset( $_SESSION['tx_captcha_string'] ) && $_SESSION['tx_captcha_string'] > '' ) {
			$captchaStr = $_SESSION['tx_captcha_string'];

			// make sure the answer given to the captcha is not empty
			if ($captchaStr != $this->gp[$this->formFieldName] || strlen(trim($this->gp[$this->formFieldName])) === 0) {
				$checkFailed = $this->getCheckFailed();
			}
		} else {
			$checkFailed = $this->getCheckFailed();
		}

		if(!$this->globals->isAjaxMode()) {
			$_SESSION['tx_captcha_string'] = '';
		}
		return $checkFailed;
	}

}
