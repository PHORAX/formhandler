<?php

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-formlogs'] = 'formhandler-foldericon';

$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
  'LLL:EXT:formhandler/Resources/Private/Language/locallang.xlf:title',
  'formlogs',
  'EXT:formhandler/Resources/Public/Icons/Extension.gif',
];
