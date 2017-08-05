<?php
namespace Typoheads\Formhandler\PreProcessor;

/*                                                                        *
     * This script is part of the TYPO3 project - inspiring people to share!  *
     *                                                                        *
     * TYPO3 is free software; you can redistribute it and/or modify it under *
     * the terms of the GNU General Public License version 2 as published by  *
     * the Free Software Foundation.                                          *
     *                                                                        *
     * This script is distributed in the hope that it will be useful, but     *
     * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
     * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
     * Public License for more details.                                       *
     *                                                                        */

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
 * plugin.Tx_Formhandler.settings.predef.multistep_example.2.validators.1.config.fieldConf.[field].errorcheck.1.notDefaultValue
 * plugin.Tx_Formhandler.settings.predef.multistep_example.2.validators.1.config.fieldConf.[field].errorcheck.1.notDefaultValue.defaultValue < plugin.Tx_Formhandler.settings.predef.multistep_example.preProcessors.1.config.1.[field].defaultValue
 *
 * @author    Johannes Feustel
 */

class LoadDefaultValues extends AbstractPreProcessor
{
    public function process()
    {
        foreach ($this->settings as $step => $stepSettings) {
            $step = preg_replace('/\.$/', '', $step);

            if ($step == 1) {
                $this->loadDefaultValuesToGP($stepSettings);
            } elseif (is_numeric($step)) {
                $this->loadDefaultValuesToSession($stepSettings, $step);
            }
        }
        return $this->gp;
    }

    /**
     * adapted from class tx_thmailformplus_pi1
     * Loads the default values to the GP Array
     *
     * @return void
     * @param array $settings
     */
    public function loadDefaultValuesToGP($settings)
    {
        if (is_array($settings)) {
            $this->setDefaultValues($settings, $this->gp);
        }
    }

    /**
     * loads the Default Setting in the Session. Used only for step 2+.
     *
     * @return void
     * @param Array $settings
     * @param int $step
     */
    private function loadDefaultValuesToSession($settings, $step)
    {
        if (is_array($settings) && $step) {
            $values = $this->globals->getSession()->get('values');
            $this->setDefaultValues($settings, $values[$step]);
            $this->globals->getSession()->set('values', $values);
        }
    }

    /**
     * Recursive method to set the GP values
     *
     * @return void
     * @param array $fields
     * @param array &$currentLevelGP
     */
    protected function setDefaultValues($fields, &$currentLevelGP)
    {
        $firstLevelFields = array_keys($fields);
        if (is_array($firstLevelFields)) {
            foreach ($firstLevelFields as $idx => $fieldName) {
                $fieldName = preg_replace('/\.$/', '', $fieldName);
                if (!isset($fields[$fieldName . '.']['defaultValue']) && is_array($fields[$fieldName . '.'])) {
                    $this->setDefaultValues($fields[$fieldName . '.'], $currentLevelGP[$fieldName]);
                } elseif (!isset($currentLevelGP[$fieldName])) {
                    $currentLevelGP[$fieldName] = $this->utilityFuncs->getSingle($fields[$fieldName . '.'], 'defaultValue');
                    if ($fields[$fieldName . '.']['defaultValue.']['separator']) {
                        $separator = $this->utilityFuncs->getSingle($fields[$fieldName . '.']['defaultValue.'], 'separator');
                        $currentLevelGP[$fieldName] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($separator, $currentLevelGP[$fieldName]);
                    }
                }
            }
        }
    }
}
