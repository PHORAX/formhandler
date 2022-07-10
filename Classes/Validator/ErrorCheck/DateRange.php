<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Validator\ErrorCheck;

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
 * Validates that a specified field's value is a valid date and between two specified dates.
 */
class DateRange extends Date {
  public function check(): string {
    $checkFailed = '';
    $date = strval($this->gp[$this->formFieldName] ?? '');

    if (strlen(trim($date)) > 0) {
      $params = (array) ($this->settings['params'] ?? []);
      $min = $this->utilityFuncs->getSingle($params, 'min');
      $max = $this->utilityFuncs->getSingle($params, 'max');
      $pattern = $this->utilityFuncs->getSingle($params, 'pattern');
      preg_match('/^[d|m|y]*(.)[d|m|y]*/i', $pattern, $res);
      $sep = $res[1];

      // normalisation of format
      $pattern = $this->utilityFuncs->normalizeDatePattern($pattern, $sep);

      // find out correct positions of "d","m","y"
      $pos1 = strpos($pattern, 'd');
      $pos2 = strpos($pattern, 'm');
      $pos3 = strpos($pattern, 'y');
      $checkdate = explode($sep, $date);
      $check_day = $checkdate[$pos1];
      $check_month = $checkdate[$pos2];
      $check_year = $checkdate[$pos3];
      if (strlen($min) > 0) {
        $min_date = GeneralUtility::trimExplode($sep, $min);
        $min_day = $min_date[$pos1];
        $min_month = $min_date[$pos2];
        $min_year = $min_date[$pos3];
        if ($check_year < $min_year) {
          $checkFailed = $this->getCheckFailed();
        } elseif ($check_year == $min_year && $check_month < $min_month) {
          $checkFailed = $this->getCheckFailed();
        } elseif ($check_year == $min_year && $check_month == $min_month && $check_day < $min_day) {
          $checkFailed = $this->getCheckFailed();
        }
      }
      if (strlen($max) > 0) {
        $max_date = GeneralUtility::trimExplode($sep, $max);
        $max_day = $max_date[$pos1];
        $max_month = $max_date[$pos2];
        $max_year = $max_date[$pos3];
        if ($check_year > $max_year) {
          $checkFailed = $this->getCheckFailed();
        } elseif ($check_year == $max_year && $check_month > $max_month) {
          $checkFailed = $this->getCheckFailed();
        } elseif ($check_year == $max_year && $check_month == $max_month && $check_day > $max_day) {
          $checkFailed = $this->getCheckFailed();
        }
      }
    }

    return $checkFailed;
  }

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $this->mandatoryParameters = ['min', 'max', 'pattern'];
  }
}
