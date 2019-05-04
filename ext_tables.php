<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE === 'BE') {


    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Typoheads.' . $_EXTKEY,
        'web',
        'log',
        'bottom',
        [
            'Module' => 'index, view, selectFields, export'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/moduleicon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml'
        ]
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_formhandler_log');


// REGISTER ICONS FOR USE IN BACKEND WIZARD
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'formhandlerElement',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:formhandler/ext_icon.gif']
);