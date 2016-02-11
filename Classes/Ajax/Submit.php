<?php
namespace Typoheads\Formhandler\Ajax;
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
*                                                                        *
* TYPO3 is free software; you can redistribute it and/or modify it under *
* the terms of the GNU General Public License version 2 as published by  *
* the Free Software Foundation.                                          *
*                                                                        *
* This script is distributed in the hope that it will be useful, but     *
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
* TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
* Public License for more details.                                       *
*                                                                        */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class calling the controller and returning the form content as JSON. This class is called via AJAX.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 */

class Submit {

	/**
	 * Main method of the class.
	 *
	 * @return string The HTML list of remaining files to be displayed in the form
	 */
	public function main() {
		$this->init();
		$content = '';

		$settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.'];
		$settings['usePredef'] = $this->globals->getSession()->get('predef');
		
		$content = $GLOBALS['TSFE']->cObj->cObjGetSingle('USER', $settings);

		$content = '{' . json_encode('form') . ':' . json_encode($content, JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS) . '}';
		print $content;
	}

	/**
	 * Initialize the class. Read GET parameters
	 *
	 * @return void
	 */
	protected function init() {
		if (isset($_GET['pid'])) {
			$this->id = intval($_GET['pid']);
		} else {
			$this->id = intval($_GET['id']);
		}

		$this->componentManager = GeneralUtility::makeInstance(\Typoheads\Formhandler\Component\Manager::class);
		$this->globals = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\Globals::class);
		$this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
		$this->utilityFuncs->initializeTSFE($this->id);

		$elementUID = intval($_GET['uid']);
		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tt_content', 'uid=' . $elementUID . $GLOBALS['TSFE']->cObj->enableFields('tt_content'));
		if(!empty($row)) {
			$GLOBALS['TSFE']->cObj->data = $row;
			$GLOBALS['TSFE']->cObj->current = 'tt_content_' . $elementUID;
		}

		$this->globals->setCObj($GLOBALS['TSFE']->cObj);
		$randomID = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('randomID'));
		$this->globals->setRandomID($randomID);
		$this->globals->setAjaxMode(TRUE);
		if(!$this->globals->getSession()) {
			$ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['Tx_Formhandler.']['settings.'];
			$sessionClass = $this->utilityFuncs->getPreparedClassName($ts['session.'], 'Session\PHP');
			$this->globals->setSession($this->componentManager->getComponent($sessionClass));
		}
		
		$this->settings = $this->globals->getSession()->get('settings');
		$this->langFiles = $this->utilityFuncs->readLanguageFiles(array(), $this->settings);

		//init ajax
		if ($this->settings['ajax.']) {
			$class = $this->utilityFuncs->getPreparedClassName($this->settings['ajax.'], 'AjaxHandler\JQuery');
			$ajaxHandler = $this->componentManager->getComponent($class);
			$this->globals->setAjaxHandler($ajaxHandler);

			$ajaxHandler->init($this->settings['ajax.']['config.']);
			$ajaxHandler->initAjax();
		}
	}

}

$obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Typoheads\Formhandler\Ajax\Submit::class);
$obj->main();
