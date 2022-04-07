<?php

if (!defined('TYPO3')) {
  exit('Access denied.');
}
if (TYPO3_MODE === 'BE') {
  \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Formhandler',
    'web',
    'log',
    'bottom',
    [
      \Typoheads\Formhandler\Controller\ModuleController::class => 'index, view, selectFields, export',
    ],
    [
      'access' => 'user,group',
      'icon' => 'EXT:formhandler/Resources/Public/Icons/moduleicon.gif',
      'labels' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_mod.xlf',
    ]
  );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_formhandler_log');
// REGISTER ICONS FOR USE IN BACKEND WIZARD
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
  'formhandlerElement',
  \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
  ['source' => 'EXT:formhandler/Resources/Public/Icons/Extension.gif']
);
