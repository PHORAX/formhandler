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
 * Validates that a specified field is a string and at least a specified count of words.
 */
class MinWordCount extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';
    $min = intval($this->utilityFuncs->getSingle((array) ($this->settings['params'] ?? []), 'value'));
    $formFieldValue = strval($this->gp[$this->formFieldName] ?? '');
    if (
      mb_strlen(trim($formFieldValue), 'utf-8') > 0
      && $min > 0
      && str_word_count(trim($formFieldValue)) < $min
    ) {
      $checkFailed = $this->getCheckFailed();
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['value'];
  }
}
