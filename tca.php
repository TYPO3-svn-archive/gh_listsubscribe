<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_ghlistsubscribe_lists"] = array (
	"ctrl" => $TCA["tx_ghlistsubscribe_lists"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,name,description,address"
	),
	"feInterface" => $TCA["tx_ghlistsubscribe_lists"]["feInterface"],
	"columns" => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_ghlistsubscribe_lists',
				'foreign_table_where' => 'AND tx_ghlistsubscribe_lists.pid=###CURRENT_PID### AND tx_ghlistsubscribe_lists.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		"name" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:gh_listsubscribe/locallang_db.xml:tx_ghlistsubscribe_lists.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "50",	
				"eval" => "required",
			)
		),
		"description" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:gh_listsubscribe/locallang_db.xml:tx_ghlistsubscribe_lists.description",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "3",
			)
		),
		"address" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:gh_listsubscribe/locallang_db.xml:tx_ghlistsubscribe_lists.address",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "100",	
				"eval" => "required",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, name, description, address")
	),
	"palettes" => array (
		"1" => array("showitem" => "starttime, endtime")
	)
);
?>