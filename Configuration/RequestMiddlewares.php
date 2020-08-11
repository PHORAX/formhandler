<?php

return [
    'frontend' => [
        'typoheads/formhandler/ajax' => [
            'target' => \Typoheads\Formhandler\Middleware\Ajax::class,
		    'after' =>  [
		        'typo3/cms-frontend/tsfe'
		        // 'typo3/cms-frontend/site'
		    ]
        ],
	]
];
