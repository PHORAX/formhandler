<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

ExtensionManagementUtility::addPItoST43(
    'formhandler',
    'pi1/class.tx_formhandler_pi1.php',
    '_pi1',
    'CType',
    0
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['formhandler'] = 'EXT:formhandler/Classes/Http/Validate.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['formhandler-removefile'] = 'EXT:formhandler/Classes/Http/RemoveFile.php';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['formhandler-ajaxsubmit'] = 'EXT:formhandler/Classes/Http/Submit.php';

// load default PageTS config from static file
ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:formhandler/Configuration/TypoScript/pageTsConfig.ts">');
