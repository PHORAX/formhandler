<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

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
 * This finisher generates a unique code for a database entry.
 * This can be used for FE user registration or newsletter registration.
 */
class GenerateAuthCode extends AbstractFinisher {
  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    $firstInsertInfo = [];
    if ($this->utilityFuncs->getSingle($this->settings, 'uid')) {
      $uidField = $this->utilityFuncs->getSingle($this->settings, 'uidField') ?: 'uid';
      $firstInsertInfo = [
        'table' => $this->utilityFuncs->getSingle($this->settings, 'table'),
        'uidField' => $uidField,
        'uid' => $this->utilityFuncs->getSingle($this->settings, 'uid'),
      ];
    } elseif (isset($this->gp['saveDB']) && is_array($this->gp['saveDB'])) {
      if (isset($this->settings['table'])) {
        $table = $this->utilityFuncs->getSingle($this->settings, 'table');
        foreach ($this->gp['saveDB'] as $idx => $insertInfo) {
          if ($insertInfo['table'] === $table) {
            $firstInsertInfo = $insertInfo;

            break;
          }
        }
      }
      if (empty($firstInsertInfo)) {
        reset($this->gp['saveDB']);
        $firstInsertInfo = current($this->gp['saveDB']);
      }
    }

    $table = $firstInsertInfo['table'];
    $uid = $firstInsertInfo['uid'];
    $uidField = $firstInsertInfo['uidField'] ?: 'uid';

    if ($table && $uid) {
      /** @var ConnectionPool $connectionPool */
      $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

      $conn = $connectionPool->getConnectionForTable($table);

      $selectFields = '*';
      if ($this->settings['selectFields']) {
        $selectFields = $this->utilityFuncs->getSingle($this->settings, 'selectFields');
      }
      $row = $conn->select(explode(',', $selectFields), $table, [$uidField => $uid])->fetchAssociative();
      if (!empty($row)) {
        $authCode = $this->generateAuthCode($row);
        $this->gp['generated_authCode'] = $authCode;

        // looking for the page, which should be used for the authCode Link
        // first look for TS-setting 'authCodePage', second look for redirect_page-setting, third use actual page
        if (isset($this->settings['authCodePage'])) {
          $authCodePage = $this->utilityFuncs->getSingle($this->settings, 'authCodePage');
        } else {
          $authCodePage = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], 'redirect_page', 'sMISC');
        }
        if (!$authCodePage) {
          $authCodePage = $GLOBALS['TSFE']->id;
        }

        // create the parameter-array for the authCode Link
        $paramsArray = array_merge($firstInsertInfo, ['authCode' => $authCode]);

        if ($this->settings['excludeParams']) {
          $excludeParams = GeneralUtility::trimExplode(',', $this->utilityFuncs->getSingle($this->settings, 'excludeParams'));
          foreach ($excludeParams as $param) {
            if (isset($paramsArray[$param])) {
              unset($paramsArray[$param]);
            }
          }
        }

        // If we have set a formValuesPrefix, add it to the parameter-array
        if ($this->settings['customFormValuesPrefix']) {
          $formValuesPrefix = $this->utilityFuncs->getSingle($this->settings, 'customFormValuesPrefix');
        } else {
          $formValuesPrefix = $this->globals->getFormValuesPrefix();
        }
        if (!empty($formValuesPrefix)) {
          $paramsArray = [$formValuesPrefix => $paramsArray];
        }
        $paramsArray['no_cache'] = 1;

        $linkConf = [
          'parameter' => $authCodePage,
          'additionalParams' => GeneralUtility::implodeArrayForUrl('', $paramsArray),
          'returnLast' => 'url',
          'useCacheHash' => 1,
          'forceAbsoluteUrl' => 1,
        ];

        $url = $this->cObj->typoLink_URL($linkConf);
        $this->gp['authCodeUrl'] = $url;
      }
    }

    return $this->gp;
  }

  /**
   * Return a hash value to send by email as an auth code.
   *
   * @param array<string, mixed> $row The submitted form data
   *
   * @return string The auth code
   */
  protected function generateAuthCode(array $row): string {
    return GeneralUtility::hmac(serialize($row), 'formhandler');
  }
}
