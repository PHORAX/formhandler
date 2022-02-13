<?php
declare(strict_types = 1);
namespace Typoheads\Formhandler\TcaFormElement;

use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

class PredefinedForm
{
    /**
     * Add predefined forms item list
     *
     * @param array $params
     */
    public function addItems(array &$params): void
    {
        $ts = $this->loadTS($params['flexParentDatabaseRow']['pid']);

        // Check if forms are available
        if (
            !is_array($ts['plugin.'] ?? null) ||
            !is_array($ts['plugin.']['tx_formhandler_pi1.'] ?? null) ||
            !is_array($ts['plugin.']['tx_formhandler_pi1.']['settings.'] ?? null) ||
            !is_array($ts['plugin.']['tx_formhandler_pi1.']['settings.']['predef.'] ?? null) ||
            count($ts['plugin.']['tx_formhandler_pi1.']['settings.']['predef.']) === 0
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
            if (!is_array($form) || !isset($form['name'])){
                continue;
            }

            $beName = $form['name'];

            // Check if form name can be translated
            $data = explode(':', $form['name']);
            if (strtolower($data[0]) === 'lll') {
                array_shift($data);
                $langFileAndKey = implode(':', $data);
                $beName = $GLOBALS['LANG']->sL('LLL:' . $langFileAndKey);
            }
            $predef[] = [$beName, $key];
        }

        if (count($predef) ==0){
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
     * Loads the TypoScript for the given page id
     *
     * @param int $pageUid
     * @return array The TypoScript setup
     */
    private function loadTS(int $pageUid): array
    {
        /** @var RootlineUtility $rootLine */
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();

        /** @var ExtendedTemplateService $TSObj */
        $TSObj = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $TSObj->tt_track = false;
        $TSObj->runThroughTemplates($rootLine);
        $TSObj->generateConfig();

        return $TSObj->setup;
    }
}
