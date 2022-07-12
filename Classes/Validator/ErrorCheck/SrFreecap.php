<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

use SJBR\SrFreecap\PiBaseApi;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This script is part of the TYPO3 project - inspiring people to share!
 *
 * TYPO3 is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by
 * the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 */

/**
 * Validates that a specified field's value matches the generated word of the extension "sr_freecap".
 */
class SrFreecap extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';
    if (ExtensionManagementUtility::isLoaded('sr_freecap')) {
      require_once ExtensionManagementUtility::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php';

      /** @var PiBaseApi $freeCap */
      $freeCap = GeneralUtility::makeInstance(PiBaseApi::class);
      if (!$freeCap->checkWord(strval($this->gp[$this->formFieldName] ?? ''))) {
        $checkFailed = $this->getCheckFailed();
      }
    }

    return $checkFailed;
  }
}
