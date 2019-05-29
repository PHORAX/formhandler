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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($checkTable);
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                $queryBuilder
                    ->select($checkField)
                    ->from($checkTable)
                    ->where(
                        $queryBuilder->expr()->eq($checkField, $queryBuilder->createNamedParameter($this->gp[$this->formFieldName]))
                    );

                $additionalWhere = $this->utilityFuncs->prepareAndWhereString($additionalWhere);
                if (!empty($additionalWhere)) {
                    $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($additionalWhere));
                }
                $showHidden = intval($this->settings['params']['showHidden']) === 1;
                if ($showHidden) {
                    $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
                }

                $stmt = $queryBuilder->execute();
                if ($stmt && $stmt->rowCount() > 0) {
                    $checkFailed = $this->getCheckFailed();
                } elseif (!$stmt) {
                    $this->utilityFuncs->debugMessage('error', [$stmt->errorInfo()], 3);
                }
            }
        }
        return $checkFailed;
    }
}
