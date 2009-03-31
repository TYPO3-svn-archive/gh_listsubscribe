<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Gregor Hermens <gregor@a-mazing.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'list subscribe' for the 'gh_listsubscribe' extension.
 *
 * @author	Gregor Hermens <gregor@a-mazing.de>
 * @package	TYPO3
 * @subpackage	tx_ghlistsubscribe
 */
class tx_ghlistsubscribe_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_ghlistsubscribe_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ghlistsubscribe_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'gh_listsubscribe';	// The extension key.
	var $pidList       = '';  // List of pids where to look for lists
	var $lists         = array();  // List data
	var $formLayout    = '';  // layout name from TS
	var $hideSingle    = false;  // Don't show list selector with only one list
	var $targetPid     = 0;  // PID for the form action, same page if empty

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		// $this->pi_USR_INT_obj = 1;
		$this->pi_loadLL();
		$this->pi_initPIflexForm();


		// initialize parameters from FF and TS
		$outputType = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'outputType');
		if(empty($outputType)) {
			$outputType = $this->conf['outputType'];
		}
		if(!in_array($outputType, array('form','list'))) {
			$outputType = 'form';
		}

		$this->pidList = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'pidList');
		if(!empty($this->pidList) and $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'recursive')) {
			$this->pidList = $this->pi_getPidList($this->pidList,99);
		}
		if(empty($this->pidList)) {
			$this->pidList = $this->conf['pidList'];
			if(!empty($this->pidList) and $this->conf['recursive']) {
				$this->pidList = $this->pi_getPidList($this->pidList,99);
			}
		}
		if(empty($this->pidList)) {
			$this->pidList = $GLOBALS['TSFE']->id;
		}


		// initialize parameters depending on $outputType
		switch($outputType) {
		case 'form':  // initialize paameters only relevant to form

			$this->hideSingle = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'hideSingle', 'sForm');

			$this->formLayout = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'formLayout', 'sForm');
			if(empty($this->formLayout) or 'ts' == $this->formLayout) {
				if(!empty($this->conf['formLayout']) and !empty($this->conf['formConf.'][$this->conf['formLayout'].'.'])) {
					$this->formLayout = $this->conf['formLayout'];
				} else {
					$this->formLayout = 'default';
				}
			}

			$this->targetPid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'targetPid', 'sForm');
			if(empty($this->targetPid)) {
				$this->targetPid = $this->conf['targetPid'];
			}
			if(empty($this->targetPid)) {
				$this->targetPid = $GLOBALS['TSFE']->id;
			}

			break;

		case 'list':  // initialize parameters only relevant to list

			$this->listLayout = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'listLayout', 'sList');
			if(empty($this->listLayout) or 'ts' == $this->listLayout) {
				if(!empty($this->conf['listLayout']) and !empty($this->conf['listConf.'][$this->conf['listLayout'].'.'])) {
					$this->listLayout = $this->conf['listLayout'];
				} else {
					$this->listLayout = 'default';
				}
			}

			break;
		}

		// get list data
		$this->getLists();


		// decide what to show
		switch($outputType) {
		case 'form':  // show or process form

			if(!empty($this->piVars['submit'])) {  // form has been submitted -> check and process form data
				$error = false;  // true if some form data is not valid
				$emailerror = false;  // true if email is not valid, triggers error message

				// Spam protection: This field is hidden by CSS, so it always should be empty
				if(!empty($this->piVars['text'])) {
					$error = true;
				}

				// check for valid email
				if(empty($this->piVars['email']) or !t3lib_div::validEmail($this->piVars['email'])) {
					$error = true;
					$emailerror = true;
				}

				// check for valid list id
				if(empty($this->piVars['liste']) or !in_array($this->piVars['liste'], array_keys($this->lists))) {
					$error = true;
				}

				// check for valid action
				if(empty($this->piVars['action']) or !in_array($this->piVars['action'], array('subscribe','unsubscribe'))) {
					$error = true;
				}

				if($error) {  // show prefilled form if error occured
					$content = $this->renderForm($emailerror);
				} else {  // process form data

					// set markes for success messages
					$markerArray = array(
						'###EMAIL###' => $this->piVars['email'],
						'###LISTNAME###' => $this->lists[$this->piVars['liste']]['name'],
						'###WEBMASTER###' => '<a href="mailto:'.$this->conf['webmaster'].'">'.$this->conf['webmaster'].'</a>',
					);

					if($this->sendmail($this->piVars['email'], $this->lists[$this->piVars['liste']]['address'], $this->piVars['action'], $this->piVars['name'])) {  // email has been sent successfully, show success message
						$content = '<p class="ghlistsubscribe-success">'.$this->cObj->substituteMarkerArray($this->pi_getLL('success_message'), $markerArray).'</p>';
					} else {  // undefined error while sending email, show error message
						$content = '<p class="ghlistsubscribe-error">'.$this->cObj->substituteMarkerArray($this->pi_getLL('error_message'), $markerArray).'</p>';
					}
				}

			} else {  // form has not been submitted -> show form
				$content = $this->renderForm();
			}

			break;

		case 'list':  // show list of lists

			$content = $this->renderList();

			break;
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Get lists data from database
	 *
	 * @return bool success
	 */
	function getLists() {
		$this->lists = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, name, description, address', 'tx_ghlistsubscribe_lists', 'pid IN( '.$this->pidList.' ) AND hidden = "0" AND deleted = "0" AND starttime < UNIX_TIMESTAMP( NOW() ) AND ( endtime = "0" OR endtime > UNIX_TIMESTAMP( NOW() ) )', '', 'name', '', 'uid');

		return true;
	}


	/**
	 * Render the HTML form
	 *
	 * @param bool $mailerror: output error message
	 * @return string the form
	 */
	function renderForm($mailerror=false) {

		// initialize form config from TS
		$formConf = $this->conf['formConf.'][$this->formLayout.'.'];
		$formConf['type'] = $this->targetPid;

		$formData = array();

		// prepare options for list selector
		$listselect = array();
		foreach($this->lists as $list) {
			$listselect[] = ($list['uid'] == $this->piVars['liste'] ? '*':'').$list['name'].'='.$list['uid'];
		}
		// list selector
		if(1 == count($listselect) and $this->hideSingle) { // hide liste selector
			$formData[] =	array('','liste=hidden', $list['uid']);
		} else {  // show list selector
			$formData[] =	array($this->pi_getLL('list').':','liste=select', implode(',', $listselect));
		}

		// name field
		if(empty($formConf['noNameField'])) {  // hide name field?
			$formData[] = array($this->pi_getLL('name').':', 'name=input', htmlspecialchars($this->piVars['name']));
		}

		// show error message for invalid email
		if($mailerror) {
			$formData[] =	array('','label',$this->pi_getLL('email_error').':');
		}

		// email field
		$formData[] =	array($this->pi_getLL('email').':','email=input', htmlspecialchars($this->piVars['email']));

		// action selector
		if(empty($formConf['subscribeOnly'])) { // show action selector
			$formData[] =	array($this->pi_getLL('action').':','action=select',('subscribe' == $this->piVars['action'] ? '*':'').$this->pi_getLL('subscribe').'=subscribe,'.('unsubscribe' == $this->piVars['action'] ? '*':'').$this->pi_getLL('unsubscribe').'=unsubscribe');

			// submit button
			$formData[] =	array('','submit=submit',$this->pi_getLL('submit'));

		} else {  // hide action selector, default to action "subscribe"

			$formData[] = array('', 'action=hidden', 'subscribe');

			// submit button with action as value
			$formData[] =	array('','submit=submit',$this->pi_getLL('subscribe'));
		}

		// generate form code
		return $this->cObj->FORM($formConf,$formData);
	}


	/**
	 * Render a list of all lists
	 *
	 * @return string the list of lists
	 */
	function renderList() {
		$content = '';
		foreach($this->lists as $list) {
			$content .= $this->cObj->stdWrap($list['name'], $this->conf['listConf.'][$this->listLayout.'.']['nameWrap.']);
			if(!empty($list['description'])) {
				$content .= $this->cObj->stdWrap($list['description'], $this->conf['listConf.'][$this->listLayout.'.']['descriptionWrap.']);
			}
		}
		$content = $this->cObj->stdWrap($content, $this->conf['listConf.'][$this->listLayout.'.']['listWrap.']);
		return $content;
	}

	/**
	 * Send mail with (un)subscribe request
	 *
	 * @param string $email: address to (un)subscribe
	 * @param string $list: address of mailing list
	 * @param string $action: 'subscribe' or 'unsubscribe'
	 * @return bool success
	 */
	function sendmail($email, $list, $action, $name='') {

		// check for valid email
		if(empty($email) or !t3lib_div::validEmail($email)) {
			return false;
		}

		// check for valid list address
		if(empty($list) or !t3lib_div::validEmail($list)) {
			return false;
		}

		// check for valid action
		if(!in_array($action, array('subscribe','unsubscribe'))) {
			return false;
		}

		// add name to mail header if not empty
		if(!empty($name)) {
			$name = preg_replace('|[@<>"\']+|', ' ', $name);
			mb_internal_encoding('UTF-8');
			$email = mb_encode_mimeheader($name, 'UTF-8', 'Q').' <'.$email.'>';
		}

		// add action part to list address: listname-action@listserver
		$listparts = explode('@', $list);
		$listaddress = $listparts[0].'-'.$action.'@'.$listparts[1];

		// send mail to list server
		t3lib_div::plainMailEncoded($listaddress, $action, $action, 'From: '.$email);
		return true;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/gh_listsubscribe/pi1/class.tx_ghlistsubscribe_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/gh_listsubscribe/pi1/class.tx_ghlistsubscribe_pi1.php']);
}
?>