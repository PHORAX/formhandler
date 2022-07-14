<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Typoheads\Formhandler\Domain\Model\Demand;
use Typoheads\Formhandler\Domain\Model\LogData;

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
 * Repository for \Typoheads\Formhandler\Domain\Model\LogData.
 *
 * @extends Repository<LogData>
 */
class LogDataRepository extends Repository {
  public function countRedirectsByByDemand(Demand $demand): int {
    $query = $this->getQueryWithConstraints($demand);

    return $query->count();
  }

  /**
   * Find by multiple uids using, seperated string.
   *
   * @param string $uids String containing uids
   *
   * @return QueryResultInterface<LogData>
   */
  public function findByUids(string $uids): QueryResultInterface {
    $uidArray = explode(',', $uids);
    $query = $this->createQuery();
    $uidConstraints = [];
    foreach ($uidArray as $key => $value) {
      $uidConstraints[] = $query->equals('uid', $value);
    }

    return $query->matching(
      $query->logicalAnd([$query->equals('deleted', 0), $query->logicalOr(
        $uidConstraints
      )])
    )->execute();
  }

  /**
   * @return QueryResultInterface<LogData>
   */
  public function findDemanded(Demand $demand): QueryResultInterface {
    $query = $this->getQueryWithConstraints($demand);
    $query->setLimit($demand->getLimit());
    $query->setOffset($demand->getOffset());

    return $query->execute();
  }

  /**
   * Initializes the repository.
   */
  public function initializeObject(): void {
    /** @var Typo3QuerySettings $querySettings */
    $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
    $querySettings->setRespectStoragePage(false);
    $this->setDefaultQuerySettings($querySettings);
  }

  /**
   * @return QueryInterface<LogData>
   */
  protected function getQueryWithConstraints(Demand $demand): QueryInterface {
    $query = $this->createQuery();
    $constraints = [$query->equals('deleted', 0)];

    if ($demand->getPid() > 0) {
      $constraints[] = $query->equals('pid', $demand->getPid());
    }

    if (strlen($demand->getIp()) > 0) {
      $constraints[] = $query->equals('ip', $demand->getIp());
    }

    if ($demand->getStartTimestamp() > 0) {
      $constraints[] = $query->greaterThanOrEqual('tstamp', $demand->getStartTimestamp());
    }
    if ($demand->getEndTimestamp() > 0) {
      $constraints[] = $query->lessThan('tstamp', $demand->getEndTimestamp());
    }

    $query->matching($query->logicalAnd($constraints));

    return $query;
  }
}
