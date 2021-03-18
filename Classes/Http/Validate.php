<?php

namespace Typoheads\Formhandler\Http;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\Validate;

$obj = GeneralUtility::makeInstance(Validate::class);
$obj->main();
