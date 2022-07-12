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
 * Abstract class for validators for Formhandler.
 */
class FileMinCount extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';

    $files = (array) ($this->globals->getSession()?->get('files') ?? []);
    $currentStep = intval($this->globals->getSession()?->get('currentStep') ?? 1);
    $lastStep = intval($this->globals->getSession()?->get('lastStep') ?? 1);
    $minCount = intval($this->utilityFuncs->getSingle((array) ($this->settings['params'] ?? []), 'minCount'));
    if (is_array($files[$this->formFieldName])
            && $currentStep > $lastStep
    ) {
      foreach ($_FILES as $idx => $info) {
        if (!is_array($info['name'][$this->formFieldName])) {
          $info['name'][$this->formFieldName] = [$info['name'][$this->formFieldName]];
        }
        if (empty($info['name'][$this->formFieldName][0])) {
          $info['name'][$this->formFieldName] = [];
        }
        if ((count($info['name'][$this->formFieldName]) + count($files[$this->formFieldName])) < $minCount) {
          $checkFailed = $this->getCheckFailed();
        }
      }
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['minCount'];
  }
}
