<?php
namespace Typoheads\Formhandler\Validator;

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
 */
class Ajax extends AbstractValidator
{

    /**
     * Array holding the configured validators
     *
     * @access protected
     * @var array
     */
    protected $validators;

    public function validate(&$errors)
    {

        //Nothing to do here
        return true;
    }

    /**
     * Validates the submitted values using given settings
     *
     * @param array &$errors Reference to the errors array to store the errors occurred
     * @return boolean
     */
    public function validateAjax($field, $gp, &$errors)
    {
        $this->loadConfig();
        if ($this->validators) {
            foreach ($this->validators as $idx => $settings) {
                if (is_array($settings['config.'])) {
                    $this->settings = $settings['config.'];
                }
            }
        }
        if (is_array($this->settings['fieldConf.'])) {
            $disableErrorCheckFields = [];
            if (isset($this->settings['disableErrorCheckFields.'])) {
                foreach ($this->settings['disableErrorCheckFields.'] as $disableCheckField => $checks) {
                    if (!strstr($disableCheckField, '.')) {
                        $checkString = $this->utilityFuncs->getSingle($this->settings['disableErrorCheckFields.'], $disableCheckField);
                        if (strlen(trim($checkString)) > 0) {
                            $disableErrorCheckFields[$disableCheckField] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
                                ',',
                                $checkString
                            );
                        } else {
                            $disableErrorCheckFields[$disableCheckField] = [];
                        }
                    }
                }
            } elseif (isset($this->settings['disableErrorCheckFields'])) {
                $fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->settings['disableErrorCheckFields']);
                foreach ($fields as $disableCheckField) {
                    $disableErrorCheckFields[$disableCheckField] = [];
                }
            }

            $restrictErrorChecks = [];
            if (isset($this->settings['restrictErrorChecks'])) {
                $restrictErrorChecks = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->settings['restrictErrorChecks']);
            }

            $fieldSettings = $this->settings['fieldConf.'][$field . '.'];

            //parse error checks
            if (is_array($fieldSettings['errorCheck.'])) {
                $counter = 0;
                $errorChecks = [];

                //set required to first position if set
                foreach ($fieldSettings['errorCheck.'] as $key => $check) {
                    if (!strstr($key, '.') && strlen(trim($check)) > 0) {
                        if (!strcmp($check, 'required') || !strcmp($check, 'file_required')) {
                            $errorChecks[$counter]['check'] = $check;
                            unset($fieldSettings['errorCheck.'][$key]);
                            $counter++;
                        }
                    }
                }

                //set other errorChecks
                foreach ($fieldSettings['errorCheck.'] as $key => $check) {
                    if (!strstr($key, '.')) {
                        $errorChecks[$counter]['check'] = $check;
                        if (is_array($fieldSettings['errorCheck.'][$key . '.'])) {
                            $errorChecks[$counter]['params'] = $fieldSettings['errorCheck.'][$key . '.'];
                        }
                        $counter++;
                    }
                }

                //foreach error checks
                foreach ($errorChecks as $idx => $check) {

                    //Skip error check if the check is disabled for this field or if all checks are disabled for this field
                    if (!empty($disableErrorCheckFields) &&
                        in_array('all', array_keys($disableErrorCheckFields)) ||
                        (
                            in_array($field, array_keys($disableErrorCheckFields)) &&
                            (
                                in_array($check['check'], $disableErrorCheckFields[$field]) ||
                                empty($disableErrorCheckFields[$field])
                            )
                        )
                    ) {
                        continue;
                    }

                    $classNameFix = ucfirst($check['check']);
                    if (strpos($classNameFix, 'Tx_') === false) {
                        $errorCheckObject = $this->componentManager->getComponent('\\Typoheads\\Formhandler\\Validator\\ErrorCheck\\' . $classNameFix);
                        $fullClassName = '\\Typoheads\\\Formhandler\\Validator\\ErrorCheck\\' . $classNameFix;
                    } else {
                        //Look for the whole error check name, maybe it is a custom check like Tx_SomeExt_ErrorCheck_Something
                        $errorCheckObject = $this->componentManager->getComponent($check['check']);
                        $fullClassName = $check['check'];
                    }
                    if (!$errorCheckObject) {
                        $this->utilityFuncs->debugMessage('check_not_found', [$fullClassName], 2);
                    }
                    if (empty($restrictErrorChecks) || in_array($check['check'], $restrictErrorChecks)) {
                        $errorCheckObject->init($gp, $check);
                        $errorCheckObject->setFormFieldName($field);
                        if ($errorCheckObject->validateConfig()) {
                            $checkFailed = $errorCheckObject->check();
                            if (strlen($checkFailed) > 0) {
                                if (!is_array($errors[$field])) {
                                    $errors[$field] = [];
                                }
                                $errors[$field][] = $checkFailed;
                            }
                        } else {
                            $this->utilityFuncs->throwException('Configuration is not valid for class "' . $fullClassName . '"!');
                        }
                    }
                }
            }
        }
        return empty($errors);
    }

    public function loadConfig()
    {
        $tsConfig = $this->globals->getSession()->get('settings');
        $this->settings = [];
        $this->validators = $tsConfig['validators.'];
        if ($tsConfig['ajax.']) {
            $this->settings['ajax.'] = $tsConfig['ajax.'];
        }
    }
}
