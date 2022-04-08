<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Typoheads\Formhandler\Domain\Model\Demand;

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
 */
class LogDataRepository extends Repository {
  /**
   * Find by multiple uids using, seperated string.
   *
   * @param string String containing uids
   */
  public function findByUids(string $uids): array|QueryResultInterface {
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

  public function findDemanded(?Demand $demand = null): array|QueryResultInterface {
    $query = $this->createQuery();
    $constraints = [$query->equals('deleted', 0)];

    if (null !== $demand) {
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
    }
    if (count($constraints) > 0) {
      $query->matching($query->logicalAnd($constraints));

      return $query->execute();
    }

    return $this->findAll();
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
}
