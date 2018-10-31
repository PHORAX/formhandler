<?php
namespace Typoheads\Formhandler\Domain\Model;

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

/**
 * Model for log data
 *
 */
class LogData extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * @var int
     * @validate TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator
     */
    protected $crdate = 0;

    /**
     * @var string
     */
    protected $ip = '';

    /**
     * @var string
     * @validate TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator
     */
    protected $params = '';

    /**
     * @var bool
     */
    protected $isSpam = 0;

    public function getCrdate()
    {
        return $this->crdate;
    }

    public function setCrdate($crdate)
    {
        $this->crdate = intval($crdate);
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getIsSpam()
    {
        return $this->isSpam;
    }

    public function setIsSpam($isSpam)
    {
        $this->isSpam = $isSpam;
    }
}
