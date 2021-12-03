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
 * Model for log data
 */
class LogData extends AbstractEntity
{

    /**
     * @var int
     * @TYPO3\CMS\Extbase\Annotation\Validate(validator="TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected int $crdate = 0;

    /**
     * @var string
     */
    protected string $ip = '';

    /**
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate(validator="TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator")
     */
    protected string $params = '';

    /**
     * @var bool
     */
    protected bool $isSpam = false;

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
}
