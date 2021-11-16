<?php

namespace Typoheads\Formhandler\Http;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\RemoveFile;

$obj = GeneralUtility::makeInstance(RemoveFile::class);
$obj->main();
