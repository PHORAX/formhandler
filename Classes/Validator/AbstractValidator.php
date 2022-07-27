<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Abstract class for validators for Formhandler.
 */
abstract class AbstractValidator extends AbstractComponent {
  /** @var array<string, string|string[]> */
  protected array $disableErrorCheckFields = [];

  /** @var string[] */
  protected array $restrictErrorChecks = [];

  /**
   * Array holding the configured validators.
   *
   * @var array<string, mixed>
   */
  protected array $validators;

  public function process(mixed &$error = null): array|string {
    return [];
  }

  /**
   * Validates the submitted values using given settings.
   *
   * @param array<string, mixed> $errors Reference to the errors array to store the errors occurred
   */
  abstract public function validate(array &$errors): bool;

  /**
   * Validates the submitted values using given settings.
   *
   * @param array<string, mixed> $gp
   * @param array<string, mixed> &$errors Reference to the errors array to store the errors occurred
   */
  abstract public function validateAjax(string $field, array $gp, array &$errors): bool;

  /**
   * @param array<string, mixed> $gp
   * @param array<string, mixed> $errors
   */
  abstract public function validateAjaxForm(array $gp, array &$errors): bool;

  /**
   * @param array<string, string|string[]> $disableErrorCheckFields
   *
   * @return array<string, string|string[]>
   */
  protected function getDisableErrorCheckFields($disableErrorCheckFields = []): array {
    if (isset($this->settings['disableErrorCheckFields.']) && is_array($this->settings['disableErrorCheckFields.'])) {
      /** @var string $disableCheckField */
      foreach ($this->settings['disableErrorCheckFields.'] as $disableCheckField => $checks) {
        if (!strstr($disableCheckField, '.')) {
          $checkString = $this->utilityFuncs->getSingle($this->settings['disableErrorCheckFields.'], $disableCheckField);
          if (strlen(trim($checkString)) > 0) {
            $disableErrorCheckFields[$disableCheckField] = GeneralUtility::trimExplode(
              ',',
              $checkString
            );
          } else {
            $disableErrorCheckFields[$disableCheckField] = [];
          }
        }
      }
    } elseif (isset($this->settings['disableErrorCheckFields'])) {
      $fields = GeneralUtility::trimExplode(',', strval($this->settings['disableErrorCheckFields']));
      foreach ($fields as $disableCheckField) {
        $disableErrorCheckFields[$disableCheckField] = [];
      }
    }

    return $disableErrorCheckFields;
  }

  /**
   * @return string[]
   */
  protected function getRestrictedErrorChecks(): array {
    $restrictErrorChecks = [];
    if (isset($this->settings['restrictErrorChecks'])) {
      $restrictErrorChecks = GeneralUtility::trimExplode(',', strval($this->settings['restrictErrorChecks']));
    }

    return $restrictErrorChecks;
  }
}
