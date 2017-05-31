<?php
namespace Typoheads\Formhandler\Domain\Repository;

    /*
     * This file is part of the TYPO3 CMS project.
     *
     * It is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License, either version 2
     * of the License, or any later version.
     *
     * For the full copyright and license information, please read the
     * LICENSE.txt file that was distributed with this source code.
     *
     * The TYPO3 project - inspiring people to share!
     */
use TYPO3\CMS\Extbase\Persistence\Repository;
use Typoheads\Formhandler\Domain\Model\Demand;

/**
 * Repository for \Typoheads\Formhandler\Domain\Model\LogData
 *
 * @author Reinhard Führicht <rf@typoheads.at>
 */
class LogDataRepository extends Repository
{

    /**
     * Initializes the repository.
     *
     * @return void
     */
    public function initializeObject()
    {
        /** @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings */
        $querySettings = $this->objectManager->get('TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Find by multiple uids using, seperated string
     *
     * @param string String containing uids
     */
    public function findByUids($uids)
    {
        $uidArray = explode(",", $uids);
        $query = $this->createQuery();
        $uidConstraints = [];
        foreach ($uidArray as $key => $value) {
            $uidConstraints[] = $query->equals('uid', $value);
        }
        return $query->matching(
            $query->logicalAnd(
                $query->equals('deleted', 0),
                $query->logicalOr(
                    $uidConstraints
                )
            )
        )->execute();
    }

    public function findDemanded(Demand $demand = NULL)
    {

        $query = $this->createQuery();
        $constraints = [$query->equals('deleted', 0)];

        if ($demand !== NULL) {
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
        } else {
            return $this->findAll();
        }

    }

}
