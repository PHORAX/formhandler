<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\TcaFormElement;

use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

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
class PredefinedForm {
  /**
   * Add predefined forms item list.
   */
  public function addItems(array &$params): void {
    $ts = $this->loadTS($params['flexParentDatabaseRow']['pid']);

    // Check if forms are available
    if (
            !isset($ts['plugin.']) || !is_array($ts['plugin.'])
            || !isset($ts['plugin.']['tx_formhandler_pi1.']) || !is_array($ts['plugin.']['tx_formhandler_pi1.'])
            || !isset($ts['plugin.']['tx_formhandler_pi1.']['settings.']) || !is_array($ts['plugin.']['tx_formhandler_pi1.']['settings.'])
            || !isset($ts['plugin.']['tx_formhandler_pi1.']['settings.']['predef.']) || !is_array($ts['plugin.']['tx_formhandler_pi1.']['settings.']['predef.'])
            || 0 === count($ts['plugin.']['tx_formhandler_pi1.']['settings.']['predef.'])
        ) {
      $params['items'][] = [
        0 => $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:be_missing_config'),
        1 => '',
      ];

      return;
    }

    $predef = [];

    // Parse all forms
    foreach ($ts['plugin.']['tx_formhandler_pi1.']['settings.']['predef.'] as $key => $form) {
      // Check if form has a name
      if (!is_array($form) || !isset($form['name'])) {
        continue;
      }

      $beName = $form['name'];

      // Check if form name can be translated
      $data = explode(':', $form['name']);
      if ('lll' === strtolower($data[0])) {
        array_shift($data);
        $langFileAndKey = implode(':', $data);
        $beName = $GLOBALS['LANG']->sL('LLL:'.$langFileAndKey);
      }
      $predef[] = [$beName, $key];
    }

    if (0 == count($predef)) {
      $params['items'][] = [
        0 => $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:be_missing_config'),
        1 => '',
      ];

      return;
    }

    // Add label
    $params['items'][] = [
      0 => $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xlf:be_please_select'),
      1 => '',
    ];

    // add to list
    $params['items'] = array_merge($params['items'], $predef);
  }

  /**
   * Loads the TypoScript for the given page id.
   *
   * @return array The TypoScript setup
   */
  private function loadTS(int $pageUid): array {
    /** @var RootlineUtility $rootlineUtility */
    $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid);
    $rootLine = $rootlineUtility->get();

    /** @var ExtendedTemplateService $TSObj */
    $TSObj = GeneralUtility::makeInstance(ExtendedTemplateService::class);
    $TSObj->tt_track = false;
    $TSObj->runThroughTemplates($rootLine);
    $TSObj->generateConfig();

    return $TSObj->setup;
  }
}
