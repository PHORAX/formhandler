<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\SyslogWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\TcaFormElement\PredefinedJs;
use Typoheads\Formhandler\TcaFormElement\SubmittedValues;

if (!defined('TYPO3')) {
  exit('Access denied.');
}

ExtensionManagementUtility::addPItoST43('formhandler', 'pi1/class.tx_formhandler_pi1.php', '_pi1', 'CType', false);

// Hook in tslib_content->stdWrap
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['formhandler'] = 'Typoheads\Formhandler\Hooks\StdWrapHook';

// load default PageTS config from static file
ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:formhandler/Configuration/TypoScript/pageTsConfig.typoscript">');

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['tx_formhandler_log'])) {
  $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['tx_formhandler_log'] = [
    'dateField' => 'tstamp',
    'expirePeriod' => 180,
  ];
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1638437365] = [
  'nodeName' => 'predefinedJs',
  'priority' => 40,
  'class' => PredefinedJs::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1638582370] = [
  'nodeName' => 'submittedValues',
  'priority' => 40,
  'class' => SubmittedValues::class,
];

/** @var IconRegistry $iconRegistry */
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon(
  'formhandler-foldericon',
  BitmapIconProvider::class,
  ['source' => 'EXT:formhandler/Resources/Public/Images/pagetreeicon.png']
);

if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['Typoheads']['Formhandler']['writerConfiguration'])) {
  $GLOBALS['TYPO3_CONF_VARS']['LOG']['Typoheads']['Formhandler']['writerConfiguration'] = [
    LogLevel::ERROR => [
      SyslogWriter::class => [],
    ],
  ];
}
