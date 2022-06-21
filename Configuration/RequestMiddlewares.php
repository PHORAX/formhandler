<?php

// Copyright JAKOTA Design Group GmbH. All rights reserved.
declare(strict_types=1);

return [
  'frontend' => [
    'typoheads/formhandler/ajax' => [
      'target' => Typoheads\Formhandler\Middleware\AjaxMiddleware::class,
      'before' => [
        'typo3/cms-redirects/redirecthandler',
      ],
      'after' => [
        '',
      ],
    ],
  ],
];
