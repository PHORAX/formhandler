<?php
namespace Typoheads\Formhandler\Utility;
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
use TYPO3\CMS\Core\SingletonInterface;

/**
 * A helper class for Formhandler to store global values
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 */
class Globals implements SingletonInterface {

	/**
	 * Holds the instance of the class
	 *
	 * @access private
	 * @var \Typoheads\Formhandler\Utility\Globals
	 */
	static private $instance = NULL;

	protected $ajaxHandler;
	protected $ajaxMode;
	protected $cObj;
	protected $debuggers;
	protected $formID;
	protected $formValuesPrefix;
	protected $gp;
	protected $langFiles;
	protected $overrideSettings;
	protected $predef;
	protected $randomID;
	protected $session;
	protected $settings;
	protected $submitted;
	protected $templateCode;
	protected $templateSuffix;

	public function setAjaxMode($mode) {
		$this->ajaxMode = $mode;
	}
	
	public function isAjaxMode() {
		return $this->ajaxMode;
	}
	
	public function setAjaxHandler($ajaxHandler) {
		$this->ajaxHandler = $ajaxHandler;
	}

	public function setCObj($cObj) {
		$this->cObj = $cObj;
	}

	public function setDebuggers($debuggers) {
		$this->debuggers = $debuggers;
	}

	public function addDebugger($debugger) {
		if(!is_array($this->debuggers)) {
			$this->debuggers = array();
		}
		$this->debuggers[] = $debugger;
	}
	
	public function setFormID($formID) {
		$this->formID = $formID;
	}
	
	public function setFormValuesPrefix($formValuesPrefix) {
		$this->formValuesPrefix = $formValuesPrefix;
	}

	public function setGP($gp) {
		$this->gp = $gp;
	}

	public function setLangFiles($langFiles) {
		$this->langFiles = $langFiles;
	}

	public function setOverrideSettings($overrideSettings) {
		$this->overrideSettings = $overrideSettings;
	}

	public function setPredef($predef) {
		$this->predef = $predef;
	}

	public function setRandomID($randomID) {
		$this->randomID = $randomID;
	}

	public function setSession($session) {
		$this->session = $session;
	}

	public function setSettings($settings) {
		$this->settings = $settings;
	}

	public function setSubmitted($submitted) {
		$this->submitted = $submitted;
	}

	public function setTemplateCode($templateCode) {
		$this->templateCode = $templateCode;
	}

	public function setTemplateSuffix($templateSuffix) {
		$this->templateSuffix = $templateSuffix;
	}

	public function getAjaxHandler() {
		return $this->ajaxHandler;
	}

	public function getCObj() {
		return $this->cObj;
	}

	public function getDebuggers() {
		if(!is_array($this->debuggers)) {
			$this->debuggers = array();
		}
		return $this->debuggers;
	}

	public function getFormID() {
		return $this->formID;
	}

	public function getFormValuesPrefix() {
		return $this->formValuesPrefix;
	}

	public function getGP() {
		if(!is_array($this->gp)) {
			$this->gp = array();
		}
		return $this->gp;
	}

	public function getLangFiles() {
		if(!is_array($this->langFiles)) {
			$this->langFiles = array();
		}
		return $this->langFiles;
	}

	public function getOverrideSettings() {
		if(!is_array($this->overrideSettings)) {
			$this->overrideSettings = array();
		}
		return $this->overrideSettings;
	}

	public function getPredef() {
		return $this->predef;
	}

	public function getRandomID() {
		return $this->randomID;
	}

	public function getSession() {
		return $this->session;
	}

	public function getSettings() {
		if(!is_array($this->settings)) {
			$this->settings = array();
		}
		return $this->settings;
	}

	public function isSubmitted() {
		return $this->submitted;
	}

	public function getTemplateCode() {
		return $this->templateCode;
	}

	public function getTemplateSuffix() {
		return $this->templateSuffix;
	}
}
