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
 * Validates that a specified field's value matches a perl regular expression.
 */
class PregMatch extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';

    $formFieldValue = strval($this->gp[$this->formFieldName] ?? '');
    if (strlen(trim($formFieldValue)) > 0) {
      $regex = $this->utilityFuncs->getSingle((array) ($this->settings['params'] ?? []), 'value');
      if ($regex && !preg_match($regex, $formFieldValue)) {
        $checkFailed = $this->getCheckFailed();
      }
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['value'];
  }
}
