<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

/*
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

use Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Validates that a specified field's value is not found in a specified db table.
 */
class IsNotInDBTable extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';

    $formFieldValue = strval($this->gp[$this->formFieldName] ?? '');

    if (strlen(trim($formFieldValue)) > 0) {
      $params = (array) ($this->settings['params'] ?? []);
      $checkTable = $this->utilityFuncs->getSingle($params, 'table');
      $checkField = $this->utilityFuncs->getSingle($params, 'field');
      $additionalWhere = $this->utilityFuncs->getSingle($params, 'additionalWhere');
      $showHidden = intval($this->utilityFuncs->getSingle($params, 'showHidden'));
      if (!empty($checkTable) && !empty($checkField)) {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($checkTable);

        /** @var FrontendRestrictionContainer $frontendRestrictionContainer */
        $frontendRestrictionContainer = GeneralUtility::makeInstance(FrontendRestrictionContainer::class);
        $queryBuilder->setRestrictions($frontendRestrictionContainer);
        $queryBuilder
          ->select($checkField)
          ->from($checkTable)
          ->where(
            $queryBuilder->expr()->eq($checkField, $queryBuilder->createNamedParameter($this->gp[$this->formFieldName]))
          )
        ;

        $additionalWhere = $this->utilityFuncs->prepareAndWhereString($additionalWhere);
        if (!empty($additionalWhere)) {
          $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($additionalWhere));
        }
        if (1 === $showHidden) {
          $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        try {
          $stmt = $queryBuilder->executeQuery();
          if ($stmt->rowCount() > 0) {
            $checkFailed = $this->getCheckFailed();
          }
        } catch (Exception $th) {
          $this->utilityFuncs->debugMessage('error', [$th->getMessage()], 3);
        }
      }
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['table', 'field'];
  }
}
