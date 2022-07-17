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
 * Demand object for log data.
 */
class Demand {
  /**
   * Calculated end timestamp.
   */
  protected int $endTimestamp = 0;

  protected string $ip = '';

  protected int $limit = 10;

  /**
   * Manual date start.
   */
  protected null|\DateTime $manualDateStart = null;

  /**
   * Manual date stop.
   */
  protected null|\DateTime $manualDateStop = null;

  protected int $page = 1;

  protected int $pid = 0;

  /**
   * Calculated start timestamp.
   */
  protected int $startTimestamp = 0;

  public function getEndTimestamp(): int {
    return $this->endTimestamp;
  }

  public function getIp(): string {
    return $this->ip;
  }

  public function getLimit(): int {
    return $this->limit;
  }

  /**
   * Get manual date start.
   */
  public function getManualDateStart(): null|\DateTime {
    return $this->manualDateStart;
  }

  /**
   * Get manual date stop.
   */
  public function getManualDateStop(): null|\DateTime {
    return $this->manualDateStop;
  }

  /**
   * Offset for the current set of records.
   */
  public function getOffset(): int {
    return ($this->page - 1) * $this->limit;
  }

  public function getPage(): int {
    return $this->page;
  }

  /**
   * @return array<non-empty-string, null|int|string>
   */
  public function getParameters(): array {
    $parameters = [];
    if (!empty($this->getIp())) {
      $parameters['ip'] = $this->getIp();
    }
    if (!empty($this->getManualDateStop())) {
      $parameters['manualDateStop'] = $this->getManualDateStop()->format('c');
    }
    if (!empty($this->getManualDateStart())) {
      $parameters['manualDateStart'] = $this->getManualDateStart()->format('c');
    }
    if (!empty($this->getPid())) {
      $parameters['pid'] = $this->getPid();
    }
    if (!empty($this->getLimit())) {
      $parameters['limit'] = $this->getLimit();
    }

    return $parameters;
  }

  public function getPid(): int {
    return $this->pid;
  }

  public function getStartTimestamp(): int {
    return $this->startTimestamp;
  }

  public function setEndTimestamp(int $endTimestamp): void {
    $this->endTimestamp = $endTimestamp;
  }

  public function setIp(string $ip = ''): void {
    $this->ip = $ip;
  }

  public function setLimit(int $limit): void {
    $this->limit = $limit;
  }

  /**
   * Set manual date start.
   *
   * @param \DateTime $manualDateStart
   */
  public function setManualDateStart(\DateTime $manualDateStart = null): void {
    $this->manualDateStart = $manualDateStart;
  }

  /**
   * Set manual date stop.
   *
   * @param \DateTime $manualDateStop
   */
  public function setManualDateStop(\DateTime $manualDateStop = null): void {
    $this->manualDateStop = $manualDateStop;
  }

  public function setPage(int $page): void {
    $this->page = $page;
  }

  public function setPid(int $pid): void {
    $this->pid = $pid;
  }

  public function setStartTimestamp(int $startTimestamp): void {
    $this->startTimestamp = $startTimestamp;
  }
}
