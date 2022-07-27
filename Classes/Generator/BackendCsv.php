<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Generator;

use ParseCsv\Csv;
use Typoheads\Formhandler\Component\AbstractComponent;

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
 * Class to generate CSV files in Backend.
 *
 * @uses export2CSV in csv.lib.php
 */
class BackendCsv extends AbstractComponent {
  /**
   * The internal CSV object.
   */
  protected Csv $csv;

  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    $fileName = $this->utilityFuncs->getSingle($this->settings, 'fileName');
    if (empty($fileName)) {
      $fileName = 'formhandler.csv';
    }
    $this->settings['fileName'] = $fileName;

    $delimiter = $this->utilityFuncs->getSingle($this->settings, 'delimiter');
    if (empty($delimiter)) {
      $delimiter = ',';
    }
    $this->settings['delimiter'] = $delimiter;

    $enclosure = $this->utilityFuncs->getSingle($this->settings, 'enclosure');
    if (empty($enclosure)) {
      $enclosure = '"';
    }
    $this->settings['enclosure'] = $enclosure;

    $encoding = $this->utilityFuncs->getSingle($this->settings, 'encoding');
    if (empty($encoding)) {
      $encoding = 'utf-8';
    }
    $this->settings['encoding'] = $encoding;
  }

  /**
   * Function to generate a CSV file from submitted form values. This function is called by Tx_Formhandler_Controller_Backend.
   *
   * @see Tx_Formhandler_Controller_Backend::generateCSV()
   */
  public function process(mixed &$error = null): array|string {
    $records = (array) ($this->settings['records'] ?? []);
    $exportFields = (array) ($this->settings['exportFields'] ?? []);

    $data = [];

    // build data array
    foreach ($records as $idx => $record) {
      if (is_array($record)) {
        if (!isset($record['params']) || !is_array($record['params'])) {
          $record['params'] = [];
        }
        foreach ($record['params'] as $subIdx => &$param) {
          if (is_array($param)) {
            $param = implode(';', $param);
          }
        }
        if (0 == count($exportFields) || in_array('pid', $exportFields)) {
          $record['params']['pid'] = $record['pid'];
        }
        if (0 == count($exportFields) || in_array('submission_date', $exportFields)) {
          $record['params']['submission_date'] = date('d.m.Y H:i:s', $record['crdate']);
        }
        if (0 == count($exportFields) || in_array('ip', $exportFields)) {
          $record['params']['ip'] = $record['ip'];
        }
        $data[] = $record['params'];
      }
    }
    if (count($exportFields) > 0) {
      foreach ($data as $idx => &$params) {
        if (is_array($params)) {
          // fill missing fields with empty value
          foreach ($exportFields as $key => $exportField) {
            if (!array_key_exists(strval($exportField), $params)) {
              $params[$exportField] = '';
            }
          }

          // remove unwanted fields
          foreach ($params as $key => $value) {
            if (!in_array($key, $exportFields)) {
              unset($params[$key]);
            }
          }
        }
      }
    }

    // sort data
    $dataSorted = [];
    foreach ($data as $idx => $array) {
      $dataSorted[] = $this->sortArrayByArray($array, $exportFields);
    }
    $data = $dataSorted;

    // create new parseCSV object.
    $csv = new Csv();
    $csv->delimiter = $csv->output_delimiter = strval($this->settings['delimiter'] ?? ',');
    $csv->enclosure = strval($this->settings['enclosure'] ?? '"');
    $csv->input_encoding = strtolower($this->getInputCharset());
    $csv->output_encoding = strtolower(strval($this->settings['encoding'] ?? 'utf-8'));
    $csv->convert_encoding = false;
    if ($csv->input_encoding !== $csv->output_encoding) {
      $csv->convert_encoding = true;
    }

    $fileName = isset($this->settings['fileName']) ? strval($this->settings['fileName']) : null;
    $csv->output($fileName, $data, $exportFields);

    exit;
  }

  /**
   * Get charset used by TYPO3.
   *
   * @return string Charset
   */
  private function getInputCharset(): string {
    if (is_object($GLOBALS['LANG']) && property_exists($GLOBALS['LANG'], 'charSet')) {
      $charset = $GLOBALS['LANG']->charSet;
    } elseif (isset($GLOBALS['TYPO3_CONF_VARS']) && is_array($GLOBALS['TYPO3_CONF_VARS']) && isset($GLOBALS['TYPO3_CONF_VARS']['BE']) && is_array($GLOBALS['TYPO3_CONF_VARS']['BE']) && isset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'])) {
      $charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
    } else {
      $charset = 'utf-8';
    }

    return $charset;
  }

  /**
   * Sorts the CSV data.
   *
   * @param array<string, mixed> $array
   * @param array<string, mixed> $orderArray
   *
   * @return array<string, mixed> The sorted array
   */
  private function sortArrayByArray(array $array, array $orderArray): array {
    $ordered = [];
    foreach ($orderArray as $idx => $key) {
      $key = strval($key);
      if (array_key_exists($key, $array)) {
        $ordered[$key] = $array[$key];
        unset($array[$key]);
      }
    }

    return $ordered + $array;
  }
}
