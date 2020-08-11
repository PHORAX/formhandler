<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// TODO: check if this causes problems (post requests should disable caching anyway)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('formhandler', 'pi1/class.tx_formhandler_pi1.php', '_pi1', 'CType', 0);
// \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('formhandler', 'pi1/class.tx_formhandler_pi1.php', '_pi1', 'CType', 1);

//Hook in tslib_content->stdWrap
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['formhandler'] = \Typoheads\Formhandler\Hooks\StdWrapHook::class;

// load default PageTS config from static file
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:formhandler/Configuration/TypoScript/pageTsConfig.ts">');

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_formhandler_log'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_formhandler_log'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 180
    ];
}

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Imaging\IconRegistry::class
);
$iconRegistry->registerIcon(
    'formhandler-foldericon',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:formhandler/Resources/Public/Images/pagetreeicon.png']
);
