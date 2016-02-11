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
*
*                                                                        */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class validating a field via AJAX.
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 */

class Validate {

	/**
	 * Main method of the class.
	 *
	 * @return string The HTML list of remaining files to be displayed in the form
	 */
	public function main() {
		$this->init();
		if ($this->fieldname) {
			$this->globals->setCObj($GLOBALS['TSFE']->cObj);
			$randomID = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('randomID'));
			$this->globals->setRandomID($randomID);
			if(!$this->globals->getSession()) {
				$ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['Tx_Formhandler.']['settings.'];
				$sessionClass = $this->utilityFuncs->getPreparedClassName($ts['session.'], 'Session\PHP');
				$this->globals->setSession($this->componentManager->getComponent($sessionClass));
			}
			$validator = $this->componentManager->getComponent('\Typoheads\Formhandler\Validator\Ajax');
			$errors = array();
			$valid = $validator->validateAjax($this->fieldname, $this->value, $errors);
			$this->settings = $this->globals->getSession()->get('settings');
			$content = '';
			if ($valid) {
				$content = $this->utilityFuncs->getSingle($this->settings['ajax.']['config.'], 'ok');
				if(strlen($content) === 0) {
					$content = '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('formhandler') . 'Resources/Public/Images/ok.png' . '" />';
				} else {
					$gp = array(
						$_GET['field'] => $_GET['value']
					);
					$view = $this->initView($content);
					$content = $view->render($gp, $errors);
				}
				$content = '<span class="success">' . $content . '</span>';
			} else {
				$content = $this->utilityFuncs->getSingle($this->settings['ajax.']['config.'], 'notOk');
				if(strlen($content) === 0) {
					$content = '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('formhandler') . 'Resources/Public/Images/notok.png' . '" />';
				} else {
					$view = $this->initView($content);
					$gp = array(
						$_GET['field'] => $_GET['value']
					);
					$content = $view->render($gp, $errors);
				}
				$content = '<span class="error">' . $content . '</span>';
			}
			print $content;
		}
	}

	/**
	 * Initialize the class. Read GET parameters
	 *
	 * @return void
	 */
	protected function init() {
		$this->fieldname = htmlspecialchars(stripslashes($_GET['field']));
		$this->value = htmlspecialchars(stripslashes($_GET['value']));
		if (isset($_GET['pid'])) {
			$this->id = intval($_GET['pid']);
		} else {
			$this->id = intval($_GET['id']);
		}
		$this->componentManager = GeneralUtility::makeInstance(\Typoheads\Formhandler\Component\Manager::class);
		$this->globals = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\Globals::class);
		$this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
		$this->globals->setAjaxMode(TRUE);
		$this->utilityFuncs->initializeTSFE($this->id);
	}

	/**
	 * Initialize the AJAX validation view.
	 *
	 * @param string $content The raw content
	 * @return Tx_Formhandler_View_AjaxValidation The view class
	 */
	protected function initView($content) {
		$viewClass = '\Typoheads\Formhandler\View\AjaxValidation';
		$view = $this->componentManager->getComponent($viewClass);
		$view->setLangFiles($this->utilityFuncs->readLanguageFiles(array(), $this->settings));
		$view->setSettings($this->settings);
		$templateName = 'AJAX';
		$template = str_replace('###fieldname###', htmlspecialchars($_GET['field']), $content);
		$template = '###TEMPLATE_' . $templateName . '###' . $template . '###TEMPLATE_' . $templateName . '###';
		$view->setTemplate($template, 'AJAX');
		return $view;
	}

}

$obj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Typoheads\Formhandler\Ajax\Validate::class);
$obj->main();
