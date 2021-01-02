<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Formhandler',
    'description' => 'The swiss army knife for all kinds of mailforms, completely new written using the MVC concept. Result: Flexibility, Flexibility, Flexibility  :-).',
    'category' => 'plugin',
    'version' => '',
    'state' => 'stable',
    'clearcacheonload' => true,
    'author' => 'Dev-Team Typoheads',
    'author_email' => 'dev@typoheads.at',
    'author_company' => 'Typoheads GmbH',
    'uploadfolder' => false,
    'createDirs' => null,
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-11.99.99',
        ]
    ]
];
