<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Http;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\Submit;

$obj = GeneralUtility::makeInstance(Submit::class);
$obj->main();
