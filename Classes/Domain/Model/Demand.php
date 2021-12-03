<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
 * Demand object for log data
 */
class Demand extends AbstractEntity
{

    /**
     * @var int
     */
    protected int $crdate = 0;

    /**
     * @var string
     */
    protected string $ip = '';

    /**
     * @var string
     */
    protected string $params = '';

    /**
     * @var bool
     */
    protected bool $isSpam = false;

    /**
     * Calculated start timestamp
     *
     * @var int
     */
    protected int $startTimestamp = 0;

    /**
     * Calculated end timestamp
     *
     * @var int
     */
    protected int $endTimestamp = 0;

    public function getCrdate(): int
    {
        return $this->crdate;
    }

    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getParams(): string
    {
        return $this->params;
    }

    public function setParams(string $params): void
    {
        $this->params = $params;
    }

    public function getIsSpam(): bool
    {
        return $this->isSpam;
    }

    public function setIsSpam(bool $isSpam): void
    {
        $this->isSpam = $isSpam;
    }

    /**
     * Get calculated start timestamp from query constraints
     *
     * @return int
     */
    public function getStartTimestamp(): int
    {
        return $this->startTimestamp;
    }

    /**
     * Set calculated start timestamp from query constraints
     *
     * @param int $timestamp
     */
    public function setStartTimestamp(int $timestamp): void
    {
        $this->startTimestamp = $timestamp;
    }

    /**
     * Get calculated end timestamp from query constraints
     *
     * @return int
     */
    public function getEndTimestamp(): int
    {
        return $this->endTimestamp;
    }

    /**
     * Set calculated end timestamp from query constraints
     *
     * @param int $timestamp
     */
    public function setEndTimestamp(int $timestamp): void
    {
        $this->endTimestamp = $timestamp;
    }
}
