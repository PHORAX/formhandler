<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Logger;

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * A logger to store submission information in DevLog.
 */
class DevLog extends AbstractLogger {
  /**
   * Logs the given values.
   */
  public function process(mixed &$error = null): array|string {
    $message = 'Form on page '.$GLOBALS['TSFE']->id.' was submitted!';
    $severity = LogLevel::INFO;
    if (1 == intval($this->settings['markAsSpam'] ?? 0)) {
      $message = 'Caught possible spamming on page '.$GLOBALS['TSFE']->id.'!';
      $severity = LogLevel::WARNING;
    }
    $logParams = $this->gp;
    if ($this->settings['excludeFields']) {
      $excludeFields = $this->utilityFuncs->getSingle($this->settings, 'excludeFields');
      $excludeFields = GeneralUtility::trimExplode(',', $excludeFields);
      foreach ($excludeFields as $excludeField) {
        unset($logParams[$excludeField]);
      }
    }

    /** @var LogManager $logManager */
    $logManager = GeneralUtility::makeInstance(LogManager::class);
    $logManager->getLogger(__CLASS__)->log($severity, $message, $logParams);

    return $this->gp;
  }
}
