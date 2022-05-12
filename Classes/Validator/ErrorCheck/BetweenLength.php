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
 * Validates that a specified field is a string and has a length between two specified values.
 */
class BetweenLength extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';
    $min = (int) ($this->utilityFuncs->getSingle($this->settings['params'], 'minValue'));
    $max = (int) ($this->utilityFuncs->getSingle($this->settings['params'], 'maxValue'));
    if (isset($this->gp[$this->formFieldName])
            && (mb_strlen($this->gp[$this->formFieldName], 'utf-8') < (int) $min
                || mb_strlen($this->gp[$this->formFieldName], 'utf-8') > (int) $max)
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
    $this->mandatoryParameters = ['minValue', 'maxValue'];
  }
}
