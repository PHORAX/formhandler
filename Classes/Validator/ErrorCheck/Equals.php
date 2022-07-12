<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

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
 * Validates that a specified field equals a specified word.
 */
class Equals extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';
    $formFieldValue = trim(strval($this->gp[$this->formFieldName] ?? ''));

    if (strlen($formFieldValue) > 0) {
      $checkValue = $this->utilityFuncs->getSingle((array) ($this->settings['params'] ?? []), 'word');
      if (strcasecmp($formFieldValue, $checkValue)) {
        // remove userfunc settings
        if (isset($this->settings['params']) && is_array($this->settings['params']) && isset($this->settings['params']['word.'])) {
          unset($this->settings['params']['word.']);
        }
        $checkFailed = $this->getCheckFailed();
      }
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['word'];
  }
}
