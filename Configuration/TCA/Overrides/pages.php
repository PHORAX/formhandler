<?php

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-formlogs'] = 'formhandler-foldericon';

$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'LLL:EXT:formhandler/Resources/Private/Language/locallang.xml:title',
    'formlogs',
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'ext_icon.gif'
    //'formhandler-foldericon'
];
