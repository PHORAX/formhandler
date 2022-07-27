<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Validator\ErrorCheck\AbstractErrorCheck;

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
 * A default validator for Formhandler providing basic validations.
 *
 * Example configuration:
 *
 * <code>
 * plugin.tx_formhandler_pi1.settings.validators.1.class = Tx_Formhandler_Validator_Default
 *
 * # single error check
 * plugin.tx_formhandler_pi1.settings.validators.1.config.fieldConf.firstname.errorCheck.1 = required
 *
 * #multiple error checks for one field
 * plugin.tx_formhandler_pi1.settings.validators.1.config.fieldConf.email.errorCheck.1 = required
 * plugin.tx_formhandler_pi1.settings.validators.1.config.fieldConf.email.errorCheck.2 = email
 *
 * #error checks with parameters
 * #since the parameter for the error check "minLength" is "value", you can use a marker ###value### in your error message.
 * #E.g. The lastname has to be at least ###value### characters long.
 * plugin.tx_formhandler_pi1.settings.validators.1.config.fieldConf.lastname.errorCheck.1 = required
 * plugin.tx_formhandler_pi1.settings.validators.1.config.fieldConf.lastname.errorCheck.2 = minLength
 * plugin.tx_formhandler_pi1.settings.validators.1.config.fieldConf.lastname.errorCheck.2.value = 2
 */
class DefaultValidator extends AbstractValidator {
  /**
   * Method to set GET/POST for this class and load the configuration.
   *
   * @param array<string, mixed> $gp       The GET/POST values
   * @param array<string, mixed> $tsConfig The TypoScript configuration
   */
  public function init(array $gp, array $tsConfig): void {
    $this->settings = $tsConfig;

    $flexformValue = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], 'required_fields', 'sMISC');
    if ($flexformValue) {
      $fields = GeneralUtility::trimExplode(',', $flexformValue);

      $fieldsConf = (array) ($this->settings['fieldConf.'] ?? []);
      foreach ($fields as $field) {
        $fieldConf = (array) ($fieldsConf[$field.'.'] ?? []);
        $errorChecks = (array) ($fieldConf['errorCheck.'] ?? []);
        if (!array_search('required', $errorChecks)) {
          array_push($errorChecks, 'required');
        }
        $fieldConf['errorCheck.'] = $errorChecks;
        $fieldsConf[$field.'.'] = $fieldConf;
      }
      $this->settings['fieldConf.'] = $fieldsConf;
    }

    $this->gp = $gp;
  }

  /**
   * Validates the submitted values using given settings.
   */
  public function validate(array &$errors): bool {
    // no config? validation returns true
    if (!is_array($this->settings['fieldConf.'])) {
      return true;
    }

    $this->disableErrorCheckFields = $this->getDisableErrorCheckFields();

    $this->restrictErrorChecks = $this->getRestrictedErrorChecks();

    if (!in_array('all', array_keys($this->disableErrorCheckFields))) {
      $errors = $this->validateRecursive($errors, $this->gp, (array) $this->settings['fieldConf.']);
    }

    $limit = intval($this->settings['messageLimit'] ?? 0);
    if ($limit > 0 || (isset($this->settings['messageLimit.']) && is_array($this->settings['messageLimit.']))) {
      $limits = (array) ($this->settings['messageLimit.'] ?? []);

      foreach ($errors as $field => $messages) {
        if (isset($limits[$field]) && $limits[$field] > 0) {
          $errors[$field] = array_slice((array) $messages, intval(-$limits[$field]));
        } elseif ($limit > 0) {
          $errors[$field] = array_slice((array) $messages, -$limit);
        }
      }
    }

    return empty($errors);
  }

  /**
   * Validates the submitted values using given settings.
   */
  public function validateAjax(string $field, array $gp, array &$errors): bool {
    // Nothing to do here
    return true;
  }

  public function validateAjaxForm(array $gp, array &$errors): bool {
    return true;
  }

  public function validateConfig(): bool {
    if (isset($this->settings['fieldConf.']) && is_array($this->settings['fieldConf.'])) {
      $fieldConf = $this->settings['fieldConf.'];
      foreach ($fieldConf as $key => $fieldSettings) {
        $fieldName = trim($key, '.');
        if (!isset($fieldSettings['errorCheck.'])) {
          $this->utilityFuncs->throwException('errorcheck_not_found', $fieldName);
        }
      }
    } else {
      $this->utilityFuncs->throwException('errorcheck_not_found', 'fieldConf');
    }

    return true;
  }

  /**
   * Recursively calls the configured errorChecks. It's possible to setup
   * errorChecks for each key in multidimensional arrays:.
   *
   * <code title="errorChecks for arrays">
   * <input type="text" name="birthdate[day]"/>
   * <input type="text" name="birthdate[month]"/>
   * <input type="text" name="birthdate[year]"/>
   * <input type="text" name="name"/>
   *
   * validators.1.config.fieldConf {
   *   birthdate {
   *     day.errorCheck {
   *       1 = betweenValue
   *       1.minValue = 1
   *       1.maxValue = 31
   *     }
   *     month.errorCheck {
   *       1 = betweenValue
   *       1.minValue = 1
   *       1.maxValue = 12
   *     }
   *     year.errorCheck {
   *       1 = minValue
   *       1.minValue = 45
   *     }
   *   }
   *   birthdate.errorCheck.1 = maxItems
   *   birthdate.errorCheck.1.value = 3
   *   name.errorCheck.1 = required
   * }
   * </code>
   *
   * @param array<string, mixed> $errors
   * @param array<string, mixed> $gp        The GET/POST values
   * @param array<string, mixed> $fieldConf
   *
   * @return array<string, mixed> The error array
   */
  protected function validateRecursive(array $errors, array $gp, array $fieldConf, ?string $rootField = null): array {
    // foreach configured form field
    foreach ($fieldConf as $key => $fieldSettings) {
      $fieldName = trim($key, '.');
      if (!array_key_exists($fieldName, $gp)) {
        $this->utilityFuncs->debugMessage('missing_field_for_error_check', [$fieldName], 2);
      }

      $errorFieldName = (null === $rootField) ? $fieldName : $rootField;

      if (is_array($fieldSettings)) {
        $tempSettings = $fieldSettings;
        if (isset($tempSettings['errorCheck.'])) {
          unset($tempSettings['errorCheck.']);
        }
        if (count($tempSettings)) {
          // Nested field-confs - do recursion:
          $errors[$fieldName] = $this->validateRecursive((array) ($errors[$fieldName] ?? []), (array) ($gp[$fieldName] ?? []), $tempSettings, $fieldName);
        }

        if (!is_array($fieldSettings['errorCheck.'] ?? null)) {
          continue;
        }

        $counter = 0;
        $errorChecks = [];

        // set required to first position if set
        foreach ($fieldSettings['errorCheck.'] as $checkKey => $check) {
          if (!strstr((string) $checkKey, '.')) {
            if (!strcmp($check, 'required') || !strcmp($check, 'file_required')) {
              $errorChecks[$counter]['check'] = $check;
              unset($fieldSettings['errorCheck.'][$checkKey]);
              ++$counter;
            }
          }
        }

        // set other errorChecks
        foreach ($fieldSettings['errorCheck.'] as $checkKey => $check) {
          if (!strstr((string) $checkKey, '.') && strlen(trim($check)) > 0) {
            $errorChecks[$counter]['check'] = $check;
            if (isset($fieldSettings['errorCheck.'][$checkKey.'.']) && is_array($fieldSettings['errorCheck.'][$checkKey.'.'])) {
              $errorChecks[$counter]['params'] = $fieldSettings['errorCheck.'][$checkKey.'.'];
            }
            ++$counter;
          }
        }

        // foreach error checks
        foreach ($errorChecks as $check) {
          // Skip error check if the check is disabled for this field or if all checks are disabled for this field
          if (!empty($this->disableErrorCheckFields)
              && in_array($errorFieldName, array_keys($this->disableErrorCheckFields))
              && (
                (
                  is_array($this->disableErrorCheckFields[$errorFieldName]) && in_array($check['check'], $this->disableErrorCheckFields[$errorFieldName])
                )
                || empty($this->disableErrorCheckFields[$errorFieldName])
              )
          ) {
            continue;
          }
          $classNameFix = ucfirst($check['check']);
          if (false === strpos($classNameFix, 'Tx_') && false === strpos($classNameFix, '\\')) {
            $fullClassName = $this->utilityFuncs->prepareClassName('\\Typoheads\\Formhandler\\Validator\\ErrorCheck\\'.$classNameFix);

            /** @var ?AbstractErrorCheck $errorCheckObject */
            $errorCheckObject = GeneralUtility::makeInstance($fullClassName);
          } else {
            // Look for the whole error check name, maybe it is a custom check like Tx_SomeExt_ErrorCheck_Something
            $fullClassName = $this->utilityFuncs->prepareClassName($check['check']);

            /** @var ?AbstractErrorCheck $errorCheckObject */
            $errorCheckObject = GeneralUtility::makeInstance($fullClassName);
          }
          if (null === $errorCheckObject) {
            $this->utilityFuncs->debugMessage('check_not_found', [$fullClassName], 2);

            continue;
          }
          if (empty($this->restrictErrorChecks) || in_array($check['check'], $this->restrictErrorChecks)) {
            $this->utilityFuncs->debugMessage('calling_class', [$fullClassName]);
            $errorCheckObject->init($gp, $check);
            $errorCheckObject->setFormFieldName($fieldName);
            if ($errorCheckObject->validateConfig()) {
              $checkFailed = $errorCheckObject->check();
              if (strlen($checkFailed) > 0) {
                if (!isset($errors[$fieldName]) || !is_array($errors[$fieldName])) {
                  $errors[$fieldName] = [];
                }
                $errors[$fieldName][] = $checkFailed;
              }
            } else {
              $this->utilityFuncs->throwException('Configuration is not valid for class "'.$fullClassName.'"!');
            }
          } else {
            $this->utilityFuncs->debugMessage('check_skipped', [$check['check']]);
          }
        }
      }
    }

    return $errors;
  }
}
