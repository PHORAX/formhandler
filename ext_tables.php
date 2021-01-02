<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon(
    'formhandlerElement',
    BitmapIconProvider::class,
    ['source' => 'EXT:formhandler/ext_icon.gif']
);
