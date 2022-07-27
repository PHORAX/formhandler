<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\PreProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * This PreProcessor adds the posibility to load default values.
 * Values fot the first step are loaded to $gp values of other steps are stored
 * to the session.
 *
 * Example configuration:
 *
 * <code>
 * preProcessors.1.class = Tx_Formhandler_PreProcessor_LoadDefaultValues
 * preProcessors.1.config.1.contact_via.defaultValue = email
 * preProcessors.1.config.2.[field1].defaultValue = 0
 * preProcessors.1.config.2.[field2].defaultValue {
 *       data = date : U
 *       strftime = %A, %e. %B %Y
 * }
 * preProcessors.1.config.2.[field3].defaultValue < plugin.tx_exampleplugin
 * <code>
 *
 * may copy the TS to the default validator settings to avoid redundancy
 * Example:
 *
 * plugin.tx_formhandler_pi1.settings.predef.multistep_example.2.validators.1.config.fieldConf.[field].errorcheck.1.notDefaultValue
 * plugin.tx_formhandler_pi1.settings.predef.multistep_example.2.validators.1.config.fieldConf.[field].errorcheck.1.notDefaultValue.defaultValue < plugin.tx_formhandler_pi1.settings.predef.multistep_example.preProcessors.1.config.1.[field].defaultValue
 *
 * @author    Johannes Feustel
 */
class LoadDefaultValues extends AbstractPreProcessor {
  /**
   * adapted from class tx_thmailformplus_pi1
   * Loads the default values to the GP Array.
   *
   * @param array<string, mixed> $settings
   */
  public function loadDefaultValuesToGP(array $settings): void {
    $this->setDefaultValues($settings, $this->gp);
  }

  public function process(mixed &$error = null): array|string {
    foreach ($this->settings as $step => $stepSettings) {
      $stepSettings = is_array($stepSettings) ? $stepSettings : [];
      $step = preg_replace('/\.$/', '', $step);
      if (is_numeric($step)) {
        $step = intval($step);
        if (1 == $step) {
          $this->loadDefaultValuesToGP($stepSettings);
        } else {
          $this->loadDefaultValuesToSession($stepSettings, $step);
        }
      }
    }

    return $this->gp;
  }

  /**
   * Recursive method to set the GP values.
   *
   * @param array<string, mixed> $fields
   * @param array<string, mixed> &$currentLevelGP
   */
  protected function setDefaultValues(array $fields, array &$currentLevelGP): void {
    $firstLevelFields = array_keys($fields);
    if (is_array($firstLevelFields)) {
      foreach ($firstLevelFields as $idx => $fieldName) {
        $fieldName = preg_replace('/\.$/', '', $fieldName);
        $field = (array) ($fields[$fieldName.'.'] ?? []);

        if (!isset($field['defaultValue'])) {
          $this->setDefaultValues($field, $currentLevelGP[$fieldName]);
        } elseif (!isset($currentLevelGP[$fieldName])) {
          $currentLevelGP[$fieldName] = $this->utilityFuncs->getSingle((array) $field, 'defaultValue');
          if (isset($field['defaultValue.']) && is_array($field['defaultValue.']) && isset($field['defaultValue.']['separator']) && !empty($field['defaultValue.']['separator'])) {
            $separator = $this->utilityFuncs->getSingle($field['defaultValue.'], 'separator');
            $currentLevelGP[$fieldName] = GeneralUtility::trimExplode($separator, $currentLevelGP[$fieldName]);
          }
        }
      }
    }
  }

  /**
   * loads the Default Setting in the Session. Used only for step 2+.
   *
   * @param array<string, mixed> $settings
   */
  private function loadDefaultValuesToSession(array $settings, int $step): void {
    if (is_array($settings) && $step) {
      $values = (array) ($this->globals->getSession()?->get('values') ?? []);
      // TODO Error with default values at step 2

      $this->setDefaultValues($settings, $values[$step]);
      $this->globals->getSession()?->set('values', $values);
    }
  }
}
