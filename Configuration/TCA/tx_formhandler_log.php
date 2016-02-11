<?php
return array (
	'ctrl' => array (
		'title' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:tx_formhandler_log',
		'label' => 'uid',
		'default_sortby' => 'ORDER BY crdate DESC',
		'crdate' => 'crdate',
		'tstamp' => 'tstamp',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('formhandler') . 'ext_icon.gif',
		'adminOnly' => 1
	),
	'interface' => array (
		'showRecordFieldList' => 'crdate,ip,params,is_spam,key_hash'
	),
	'columns' => array (
		'crdate' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:tx_formhandler_log.submission_date',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'ip' => array (
			'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:tx_formhandler_log.ip',
			'config' => array (
				'type' => 'input'
			)
		),
		'params' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:tx_formhandler_log.params',
			'config' => array (
				'type' => 'user',
				'userFunc' => 'Typoheads\Formhandler\Utility\TcaUtility->getParams'
			)
		),
		'is_spam' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:tx_formhandler_log.is_spam',
			'config' => array (
				'type' => 'check'
			)
		),
		'uid' => array (
			'label' => '',
			'config' => array (
				'type' => 'none'
			)
		),
		'pid' => array (
			'label' => '',
			'config' => array (
				'type' => 'none'
			)
		),
		'tstamp' => array (
			'label' => '',
			'config' => array (
				'type' => 'none'
			)
		),
		'key_hash' => array (
			'label' => '',
			'config' => array (
				'type' => 'none'
			)
		),
		'unique_hash' => array (
			'label' => '',
			'config' => array (
				'type' => 'none'
			)
		)
	),
	'types' => array (
		'0' => array(
			'showitem' => 'crdate,ip,params,is_spam'
		)
	)
);

?>