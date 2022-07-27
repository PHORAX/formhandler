<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Logger;

use TYPO3\CMS\Core\Database\ConnectionPool;
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
 * A logger to store submission information in TYPO3 database.
 */
class DB extends AbstractLogger {
  /**
   * Logs the given values.
   */
  public function process(mixed &$error = null): array|string {
    // set params
    $table = 'tx_formhandler_log';

    $doDisableIPlog = $this->utilityFuncs->getSingle($this->settings, 'disableIPlog');
    $fields['ip'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
    if (1 === (int) $doDisableIPlog) {
      unset($fields['ip']);
    }
    $fields['tstamp'] = time();
    $fields['crdate'] = time();
    $fields['pid'] = $this->utilityFuncs->getSingle($this->settings, 'pid');
    if (!$fields['pid']) {
      $fields['pid'] = $GLOBALS['TSFE']->id;
    }
    ksort($this->gp);
    $keys = array_keys($this->gp);

    $logParams = $this->gp;

    if (isset($this->settings['fields.']) && is_array($this->settings['fields.'])) {
      foreach ($this->settings['fields.'] as $field => $fieldConf) {
        $field = str_replace('.', '', $field);
        if ($fieldConf['ifIsEmpty'] && (empty($logParams[$field]) || !isset($logParams[$field]))) {
          $value = $this->utilityFuncs->getSingle($fieldConf, 'ifIsEmpty');
          $logParams[$field] = $value;
        }
        if (1 === (int) $this->utilityFuncs->getSingle($fieldConf, 'nullIfEmpty') && (empty($logParams[$field]) || !isset($logParams[$field]))) {
          unset($logParams[$field]);
        }
      }
    }
    if (isset($this->settings['excludeFields'])) {
      $excludeFields = $this->utilityFuncs->getSingle($this->settings, 'excludeFields');
      $excludeFields = GeneralUtility::trimExplode(',', $excludeFields);
      foreach ($excludeFields as $excludeField) {
        unset($logParams[$excludeField]);
      }
    }

    if (isset($this->settings['fieldOrder'])) {
      $fieldOrder = $this->utilityFuncs->getSingle($this->settings, 'fieldOrder');
      $fieldOrder = GeneralUtility::trimExplode(',', $fieldOrder);
      $orderedFields = $this->parseFieldOrder($fieldOrder);
      $logParams = $this->sortFields($logParams, $orderedFields);
    }
    $serialized = serialize($logParams);
    $hash = md5(serialize($keys));
    $uniqueHash = sha1(sha1($serialized).$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'].time().$this->globals->getRandomID());
    $fields['params'] = $serialized;
    $fields['key_hash'] = $hash;
    $fields['unique_hash'] = $uniqueHash;

    if (1 == intval($this->settings['markAsSpam'] ?? 0)) {
      $fields['is_spam'] = 1;
    }

    // query the database
    /** @var ConnectionPool $connectionPool */
    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    $conn = $connectionPool->getConnectionForTable($table);

    $conn->insert($table, $fields);
    $insertedUID = (int) $conn->lastInsertId($table);

    $sessionValues = [
      'inserted_uid' => $insertedUID,
      'inserted_tstamp' => $fields['tstamp'],
      'key_hash' => $hash,
      'unique_hash' => $uniqueHash,
    ];
    $this->globals->getSession()?->setMultiple($sessionValues);
    $this->gp['inserted_uid'] = $insertedUID;
    $this->gp[$table.'_inserted_uid'] = $this->gp['inserted_uid'];

    if (1 !== (int) $this->utilityFuncs->getSingle($this->settings, 'nodebug')) {
      $this->utilityFuncs->debugMessage('logging', [$table, implode(',', $fields)]);
      if ($conn->errorInfo()) {
        $this->utilityFuncs->debugMessage('error', [$conn->errorInfo()], 3);
      }
    }

    return $this->gp;
  }

  /**
   * @param array<string, mixed> $array
   * @param string[]             $items
   *
   * @return array<string, mixed>
   */
  protected function createDeep(array $array, array $items): array {
    if (count($items) > 0) {
      $item = array_shift($items);
      if (!is_array($array[$item])) {
        $array[$item] = [];
      }
      $array[$item] = $this->createDeep($array[$item], $items);
    }

    return $array;
  }

  /**
   * @param string[]             $order
   * @param array<string, mixed> $orderedFields
   *
   * @return array<string, mixed>
   */
  protected function parseFieldOrder(array $order, array $orderedFields = []): array {
    foreach ($order as $fieldName) {
      if (false !== strpos($fieldName, '|')) {
        $parts = explode('|', $fieldName);
        $orderedFields = $this->createDeep($orderedFields, $parts);
      } else {
        $orderedFields[$fieldName] = [];
      }
    }

    return $orderedFields;
  }

  /**
   * @param array<string, mixed> $params
   * @param array<string, mixed> $order
   * @param array<string, mixed> $sortedParams
   *
   * @return array<string, mixed>
   */
  protected function sortFields(array $params, array $order, array $sortedParams = []): array {
    foreach ($order as $fieldName => $subItems) {
      if (isset($params[$fieldName])) {
        if (!is_array($subItems) || (is_array($subItems) && 0 === count($subItems))) {
          $sortedParams[$fieldName] = $params[$fieldName];
        } elseif (!is_array($sortedParams[$fieldName])) {
          $sortedParams[$fieldName] = [];
          $sortedParams[$fieldName] = $this->sortFields(
            (array) ($params[$fieldName] ?? []),
            (array) ($order[$fieldName] ?? []),
            $sortedParams[$fieldName]
          );
        }
      }
    }

    return $sortedParams;
  }
}
