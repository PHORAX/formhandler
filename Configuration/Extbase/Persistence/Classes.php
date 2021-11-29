<?php

declare(strict_types=1);

return [
    \Typoheads\Formhandler\Domain\Model\LogData::class => [
        'tableName' => 'tx_formhandler_log',
        'properties' => [
            'crdate' => [
                'fieldname' => 'crdate',
            ],
            'isSpam' => [
                'fieldname' => 'isSpam',
            ],
            'params' => [
                'fieldname' => 'params',
            ],
            'ip' => [
                'fieldname' => 'ip',
            ],
        ],
    ],
    \Typoheads\Formhandler\Domain\Model\Demand::class => [
        'tableName' => 'tx_formhandler_log',
        'properties' => [
            'isSpam' => [
                'fieldName' => 'is_spam',
            ],
        ],
    ],
];
