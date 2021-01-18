<?php

$EM_CONF[$_EXTKEY] = array(
  'title' => 'Formhandler',
  'description' => 'The swiss army knife for all kinds of mailforms, completely new written using the MVC concept. Result: Flexibility, Flexibility, Flexibility  :-).',
  'category' => 'plugin',
  'version' => '2.4.1e3',
  'state' => 'stable',
  'clearcacheonload' => true,
  'author' => 'Dev-Team Typoheads',
  'author_email' => 'dev@typoheads.at',
  'author_company' => 'Typoheads GmbH',
  'constraints' => array(
    'depends' => array(
      'typo3' => '10.4.0',
	  'typo3db_legacy' => '1.1.0',
    ),
  ),
  'uploadfolder' => false,
  'createDirs' => null
);
