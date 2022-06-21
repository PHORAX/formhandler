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
  public function process(): array {
    return [];
  }

  /**
   * Validates the submitted values using given settings.
   *
   * @param array $errors Reference to the errors array to store the errors occurred
   */
  abstract public function validate(array &$errors): bool;

  protected function getDisableErrorCheckFields($disableErrorCheckFields = []) {
    if (isset($this->settings['disableErrorCheckFields.'])) {
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
      $fields = GeneralUtility::trimExplode(',', $this->settings['disableErrorCheckFields']);
      foreach ($fields as $disableCheckField) {
        $disableErrorCheckFields[$disableCheckField] = [];
      }
    }

    return $disableErrorCheckFields;
  }

  protected function getRestrictedErrorChecks() {
    $restrictErrorChecks = [];
    if (isset($this->settings['restrictErrorChecks'])) {
      $restrictErrorChecks = GeneralUtility::trimExplode(',', $this->settings['restrictErrorChecks']);
    }

    return $restrictErrorChecks;
  }

  /**
   * Method to parse a conditions block of the TS setting "if".
   *
   * @param array $settings The settings of this form
   */
  protected function parseConditionsBlock(array $settings): array {
    if (!isset($settings['if.'])) {
      return $settings;
    }
    foreach ($settings['if.'] as $idx => $conditionSettings) {
      $conditions = $conditionSettings['conditions.'];
      $orConditions = [];
      foreach ($conditions as $subIdx => $andConditions) {
        $results = [];
        foreach ($andConditions as $subSubIdx => $andCondition) {
          $result = strval($this->utilityFuncs->getConditionResult($andCondition, $this->gp));
          $results[] = ($result ? 'TRUE' : 'FALSE');
        }
        $orConditions[] = '('.implode(' && ', $results).')';
      }
      $finalCondition = '('.implode(' || ', $orConditions).')';

      $evaluation = false;
      eval('$evaluation = '.$finalCondition.';');

      // @phpstan-ignore-next-line
      if ($evaluation) {
        $newSettings = $conditionSettings['isTrue.'];
        if (is_array($newSettings)) {
          $settings = $this->utilityFuncs->mergeConfiguration($settings, $newSettings);
        }
      } else {
        $newSettings = $conditionSettings['else.'] ?? '';
        if (is_array($newSettings)) {
          $settings = $this->utilityFuncs->mergeConfiguration($settings, $newSettings);
          // test
        }
      }
    }

    return $settings;
  }
}
