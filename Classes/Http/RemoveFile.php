<?php
namespace Typoheads\Formhandler\Http;

$obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Typoheads\Formhandler\Ajax\RemoveFile::class);
$obj->main();
