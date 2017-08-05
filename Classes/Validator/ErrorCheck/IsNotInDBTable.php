<?php
namespace Typoheads\Formhandler\Validator\ErrorCheck;

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
 * Validates that a specified field's value is not found in a specified db table
 */
class IsNotInDBTable extends AbstractErrorCheck
{
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->mandatoryParameters = ['table', 'field'];
    }

    public function check()
    {
        $checkFailed = '';

        if (isset($this->gp[$this->formFieldName]) && strlen(trim($this->gp[$this->formFieldName])) > 0) {
            $checkTable = $this->utilityFuncs->getSingle($this->settings['params'], 'table');
            $checkField = $this->utilityFuncs->getSingle($this->settings['params'], 'field');
            $additionalWhere = $this->utilityFuncs->getSingle($this->settings['params'], 'additionalWhere');
            if (!empty($checkTable) && !empty($checkField)) {
                $additionalWhere = $this->utilityFuncs->prepareAndWhereString($additionalWhere);
                $where = $checkField . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->gp[$this->formFieldName], $checkTable) . $additionalWhere;
                $showHidden = intval($this->settings['params']['showHidden']) === 1 ? 1 : 0;
                $where .= $GLOBALS['TSFE']->sys_page->enableFields($checkTable, $showHidden);
                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($checkField, $checkTable, $where);
                if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
                    $checkFailed = $this->getCheckFailed();
                } elseif (!$res) {
                    $this->utilityFuncs->debugMessage('error', [$GLOBALS['TYPO3_DB']->sql_error()], 3);
                }
                $GLOBALS['TYPO3_DB']->sql_free_result($res);
            }
        }
        return $checkFailed;
    }
}
