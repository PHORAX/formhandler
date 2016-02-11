<?php
namespace Typoheads\Formhandler\Validator\ErrorCheck;
class FileMaxTotalSize extends AbstractErrorCheck {

	public function init($gp, $settings) {
		parent::init($gp, $settings);
		$this->mandatoryParameters = array('maxTotalSize');
	}

	public function check() {
		$checkFailed = '';
		$maxSize = $this->utilityFuncs->getSingle($this->settings['params'], 'maxTotalSize');
		$size = 0;

		// first we check earlier uploaded files
		$olderFiles = $this->globals->getSession()->get('files');
		foreach ((array) $olderFiles[$this->formFieldName] as $olderFile) {
			$size += intval($olderFile['size']);
		}

		// last we check currently uploaded file
		foreach ($_FILES as $sthg => &$files) {
			if(!is_array($files['name'][$this->formFieldName])) {
				$files['name'][$this->formFieldName] = array($files['name'][$this->formFieldName]);
			}
			if (strlen($files['name'][$this->formFieldName][0]) > 0 && $maxSize) {
				if(!is_array($files['size'][$this->formFieldName])) {
					$files['size'][$this->formFieldName] = array($files['size'][$this->formFieldName]);
				}
				foreach($files['size'][$this->formFieldName] as $fileSize) {
					$size += $fileSize;
				}
				if($size > $maxSize) {
					unset($files);
					$checkFailed = $this->getCheckFailed();
				}
			}
		}
		return $checkFailed;
	}

}
