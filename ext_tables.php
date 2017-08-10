<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE === 'BE') {

    $GLOBALS['TCA']['tt_content']['types'][$_EXTKEY . '_pi1']['showitem'] = '
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,pi_flexform,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.behaviour,
		--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
	';

    // Add flexform field to plugin options
    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';

    $file = 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_ds.xml';

    // Add flexform DataStructure
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', $file, $_EXTKEY . '_pi1');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
            $_EXTKEY . '_pi1'
        ],
        'CType'
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Typoheads.' . $_EXTKEY,
        'web',
        'log',
        'bottom',
        [
            'Module' => 'index, view, selectFields, export, deleteLogRows'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/moduleicon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml'
        ]
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/ExampleConfiguration', 'Example Configuration');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_formhandler_log');

$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:title',
    'formlogs',
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif'
];

// REGISTER ICONS FOR USE IN BACKEND WIZARD
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'formhandlerElement',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:formhandler/ext_icon.gif']
);