<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

use Typoheads\Formhandler\Component\AbstractComponent;

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
 * Abstract class for error checks for Formhandler.
 */
abstract class AbstractErrorCheck extends AbstractComponent {
  protected string $formFieldName = '';

  /** @var string[] */
  protected array $mandatoryParameters = [];

  /**
   * Sets the suitable string for the checkFailed message parsed in view.
   *
   * @return string if the check failed, the string contains the name of the failed check plus the parameters and values
   */
  abstract public function check(): string;

  public function process(mixed &$error = null): array|string {
    return [];
  }

  public function setFormFieldName(string $name): void {
    $this->formFieldName = $name;
  }

  public function validateConfig(): bool {
    $valid = true;
    if (!$this->formFieldName) {
      $this->utilityFuncs->throwException('error_checks_form_field_name_missing', $this->settings['check']);
    }

    if (!empty($this->mandatoryParameters)) {
      if (!isset($this->settings['params'])) {
        $this->utilityFuncs->throwException('error_checks_parameters_missing', $this->settings['check'], implode(',', $this->mandatoryParameters));
      }
      foreach ($this->mandatoryParameters as $param) {
        if (!isset($this->settings['params']) || !is_array($this->settings['params']) || !isset($this->settings['params'][$param])) {
          $this->utilityFuncs->throwException('error_checks_unsufficient_parameters', $param, $this->settings['check']);
        }
      }
    }

    return $valid;
  }

  /**
   * Sets the suitable string for the checkFailed message parsed in view.
   *
   * @return string The check failed string
   */
  protected function getCheckFailed(): string {
    $parts = explode('\\', get_class($this));
    $checkFailed = lcfirst(array_pop($parts));
    if (isset($this->settings['params']) && is_array($this->settings['params'])) {
      $checkFailed .= ';';
      foreach ($this->settings['params'] as $key => $value) {
        $checkFailed .= $key.'::'.$this->utilityFuncs->getSingle($this->settings['params'], $key).';';
      }
      $checkFailed = substr($checkFailed, 0, strlen($checkFailed) - 1);
    }

    return $checkFailed;
  }
}
