<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
 * Demand object for log data.
 */
class Demand extends AbstractEntity {
  protected int $crdate = 0;

  /**
   * Calculated end timestamp.
   */
  protected int $endTimestamp = 0;

  protected string $ip = '';

  protected bool $isSpam = false;

  protected string $params = '';

  /**
   * Calculated start timestamp.
   */
  protected int $startTimestamp = 0;

  public function getCrdate(): int {
    return $this->crdate;
  }

  /**
   * Get calculated end timestamp from query constraints.
   */
  public function getEndTimestamp(): int {
    return $this->endTimestamp;
  }

  public function getIp(): string {
    return $this->ip;
  }

  public function getIsSpam(): bool {
    return $this->isSpam;
  }

  public function getParams(): string {
    return $this->params;
  }

  /**
   * Get calculated start timestamp from query constraints.
   */
  public function getStartTimestamp(): int {
    return $this->startTimestamp;
  }

  public function setCrdate(int $crdate): void {
    $this->crdate = $crdate;
  }

  /**
   * Set calculated end timestamp from query constraints.
   */
  public function setEndTimestamp(int $timestamp): void {
    $this->endTimestamp = $timestamp;
  }

  public function setIp(string $ip): void {
    $this->ip = $ip;
  }

  public function setIsSpam(bool $isSpam): void {
    $this->isSpam = $isSpam;
  }

  public function setParams(string $params): void {
    $this->params = $params;
  }

  /**
   * Set calculated start timestamp from query constraints.
   */
  public function setStartTimestamp(int $timestamp): void {
    $this->startTimestamp = $timestamp;
  }
}
