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
 * Validates that a specified field is a string and at least a specified count of characters long.
 */
class MinLength extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';
    $min = $this->utilityFuncs->getSingle($this->settings['params'], 'value');
    if (isset($this->gp[$this->formFieldName])
            && mb_strlen(trim($this->gp[$this->formFieldName]), 'utf-8') > 0
            && (int) $min > 0
            && mb_strlen(trim($this->gp[$this->formFieldName]), 'utf-8') < (int) $min
        ) {
      $checkFailed = $this->getCheckFailed();
    }

    return $checkFailed;
  }

  /**
   * @param array<string, mixed> $gp       The get/post parameters
   * @param array<string, mixed> $settings An array with settings
   */
  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['value'];
  }
}
