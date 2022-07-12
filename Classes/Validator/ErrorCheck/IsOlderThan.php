<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

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
 * Validates that a person is older than a specified amount of years by converting a specified date field's value to a timestamp.
 */
class IsOlderThan extends AbstractErrorCheck {
  public function check(): string {
    $checkFailed = '';

    $date = strval($this->gp[$this->formFieldName] ?? '');
    if (strlen(trim($date)) > 0) {
      $params = (array) ($this->settings['params'] ?? []);
      $dateFormat = $this->utilityFuncs->getSingle($params, 'dateFormat');
      $mandatoryYears = intval($this->utilityFuncs->getSingle($params, 'years'));
      $timestamp = $this->utilityFuncs->dateToTimestamp($date, $dateFormat);
      $years = $this->getDateDifference($timestamp);
      if ($years < $mandatoryYears) {
        $checkFailed = $this->getCheckFailed();
      }
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['dateFormat', 'years'];
  }

  protected function getDateDifference(int $timestamp): int {
    $now = time();
    $years = intval(date('Y', $now)) - intval(date('Y', $timestamp));

    // get months of dates
    $monthsTimestamp = date('n', $timestamp);
    $monthsNow = date('n', $now);

    // get days of dates
    $daysTimestamp = date('j', $timestamp);
    $daysNow = date('j', $now);

    // if date not reached yet this year, we need to remove one year.
    if ($monthsNow < $monthsTimestamp || ($monthsNow === $monthsTimestamp && $daysNow < $daysTimestamp)) {
      --$years;
    }

    return $years;
  }
}
