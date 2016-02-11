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
 *
 * $Id$
 *                                                                        */
use TYPO3\CMS\Core\SingletonInterface;

/**
 * A class providing helper functions for Formhandler
 *
 * @author	Reinhard Führicht <rf@typoheads.at>
 */
class GeneralUtility implements SingletonInterface {

	/**
	 * The global Formhandler values
	 *
	 * @access protected
	 * @var \Typoheads\Formhandler\Utility\Globals
	 */
	protected $globals;

	public function __construct() {
		$this->globals = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\Globals::class);
	}

	/**
	 * Returns the absolute path to the document root
	 *
	 * @return string
	 */
	public function getDocumentRoot() {
		return PATH_site;
	}
	
	public function getMergedGP() {
		$gp = array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST());
		$prefix = $this->globals->getFormValuesPrefix();
		if ($prefix) {
			$gp = $gp[$prefix];
		}

		/*
		 * Unset key "saveDB" to prevent conflicts with information set by Finisher_DB
		 */
		unset($gp['saveDB']);
		return $gp;
	}

	/**
	 * Returns the absolute path to the TYPO3 root
	 *
	 * @return string
	 */
	public function getTYPO3Root() {
		$path = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_FILENAME');
		$path = str_replace('/index.php', '', $path);
		return $path;
	}

	/**
	 * Adds needed prefix to class name if not set in TS
	 *
	 * @param string $className
	 * @return string
	 */
	public function prepareClassName($className) {
		$className = ltrim($className, '\\');
		$className = str_replace('Tx_Formhandler_', 'Typoheads\\Formhandler\\', $className);
		if(strstr($className, '_') !== FALSE) {
			$className = str_replace('_', '\\', $className);
		}
		if (substr_count($className, '\\') === 1 && substr($className, 0, 11) !== '\\Typoheads\\') {
			$className = 'Typoheads\\Formhandler\\' . $className;
		}
		if($className === 'Typoheads\\Formhandler\\Validator\\Default') {
			$className = 'Typoheads\\Formhandler\\Validator\\DefaultValidator';
		}
		$className = ltrim($className, '\\');
		return $className;
	}

	/**
	 * copied from class tslib_content
	 *
	 * Substitutes markers in given template string with data of marker array
	 *
	 * @param 	string
	 * @param	array
	 * @return	string
	 */
	public function substituteMarkerArray($content,$markContentArray) {
		if (is_array($markContentArray)) {
			reset($markContentArray);
			foreach ($markContentArray as $marker => $markContent) {
				$content = str_replace($marker, $markContent, $content);
			}
		}
		return $content;
	}

	/**
	 *
	 * Returns the first subpart encapsulated in the marker, $marker (possibly present in $content as a HTML comment)
	 *
	 * @param	string	Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
	 * @param	string	Marker string, eg. "###CONTENT_PART###"
	 * @return	string
	 */
	public function getSubpart($content, $marker) {
		$start = strpos($content, $marker);
		if ($start === FALSE)	{
			return '';
		}
		$start += strlen($marker);
		$stop = strpos($content, $marker, $start);
		$content = substr($content, $start, ($stop - $start));
		$matches = array();
		if (preg_match('/^([^\<]*\-\-\>)(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches) === 1)	{
			return $matches[2];
		}
		$matches = array();
		if (preg_match('/(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches) === 1)	{
			return $matches[1];
		}
		$matches = array();
		if (preg_match('/^([^\<]*\-\-\>)(.*)$/s', $content, $matches) === 1)	{
			return $matches[2];
		}
		return $content;
	}

	/**
	 * Read template file set in flexform or TypoScript, read the file's contents to $this->templateFile
	 *
	 * @param $settings The formhandler settings
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	public function readTemplateFile($templateFile, &$settings) {
		$templateCode = FALSE;
		//template file was not set in flexform, search TypoScript for setting
		if (!$templateFile) {
			if (!$settings['templateFile'] && !$settings['templateFile.']) {
				return '';
			}
			$templateFile = $settings['templateFile'];

			if (isset($settings['templateFile.']) && is_array($settings['templateFile.'])) {
				$templateFile = $this->getSingle($settings, 'templateFile');
				if ($this->isTemplateFilePath($templateFile)) {
					$templateFile = $this->resolvePath($templateFile);
					if (!@file_exists($templateFile)) {
						$this->throwException('template_file_not_found', $templateFile);
					}
					$templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile);
				} else {

					//The setting "templateFile" was a cObject which returned HTML content. Just use that as template code.
					$templateCode = $templateFile;
				}
			} else {
				$templateFile = $this->resolvePath($templateFile);
				if (!@file_exists($templateFile)) {
					$this->throwException('template_file_not_found', $templateFile);
				}
				$templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile);
			}
		} else {
			if ($this->isTemplateFilePath($templateFile)) {
				$templateFile = $this->resolvePath($templateFile);
				if (!@file_exists($templateFile)) {
					$this->throwException('template_file_not_found', $templateFile);
				}
				$templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile);
			} else {
				// given variable $templateFile already contains the template code
				$templateCode = $templateFile;
			}
		}
		if (strlen($templateCode) === 0) {
			$this->throwException('empty_template_file', $templateFile);
		}
		if (stristr($templateCode, '###TEMPLATE_') === FALSE) {
			$this->throwException('invalid_template_file', $templateFile);
		}
		return $templateCode;
	}

	/**
	 * Read language file set in flexform or TypoScript, read the file's path to $this->langFile
	 *
	 * @param $settings The formhandler settings
	 * @return void
	 * @author	Reinhard Führicht <rf@typoheads.at>
	 */
	public function readLanguageFiles($langFiles, &$settings) {

		//language file was not set in flexform, search TypoScript for setting
		if (!$langFiles) {
			$langFiles = array();
			if (isset($settings['langFile']) && !isset($settings['langFile.'])) {
				array_push($langFiles, $this->resolveRelPathFromSiteRoot($settings['langFile']));
			} elseif (isset($settings['langFile']) && isset($settings['langFile.'])) {
				array_push($langFiles, $this->getSingle($settings, 'langFile'));
			} elseif (isset($settings['langFile.']) && is_array($settings['langFile.'])) {
				foreach ($settings['langFile.'] as $key => $langFile) {
					if (FALSE === strpos($key, '.')) {
						if (is_array($settings['langFile.'][$key . '.'])) {
							array_push($langFiles, $this->getSingle($settings['langFile.'], $key));
						} else {
							array_push($langFiles, $this->resolveRelPathFromSiteRoot($langFile));
						}
					}
				}
			}
		}
		foreach ($langFiles as $idx => &$langFile) {
			$langFile = $this->convertToRelativePath($langFile);
		}
		return $langFiles;
	}

	public function getTranslatedMessage($langFiles, $key) {
		$message = '';
		if (!is_array($langFiles)) {
			$message = trim($GLOBALS['TSFE']->sL('LLL:' . $langFiles . ':' . $key));
		} else {
			foreach ($langFiles as $idx => $langFile) {
				if (strlen(trim($GLOBALS['TSFE']->sL('LLL:' . $langFile . ':' . $key))) > 0) {
					$message = trim($GLOBALS['TSFE']->sL('LLL:' . $langFile . ':' . $key));
				}
			}
		}
		return $message;
	}

	public function getSingle($arr, $key) {
		if(!is_array($arr)) {
			return $arr;
		}
		if (!is_array($arr[$key . '.'])) {
			return $arr[$key];
		}
		if (!isset($arr[$key . '.']['sanitize'])) {
			$arr[$key . '.']['sanitize'] = 1;
		}
		if(!$this->isValidCObject($arr[$key])) {
			return $arr[$key];
		}
		return $this->globals->getCObj()->cObjGetSingle($arr[$key], $arr[$key . '.']);
	}

	public function isValidCObject($str) {
		return (
			$str === 'CASE' || $str === 'CLEARGIF' || $str === 'COA' || $str === 'COA_INT' ||
			$str === 'COLUMNS' || $str === 'CONTENT' || $str === 'CTABLE' || $str === 'EDITPANEL' ||
			$str === 'FILE' || $str === 'FILES' || $str === 'FLUIDTEMPLATE' || $str === 'FORM' ||
			$str === 'HMENU' || $str === 'HRULER' || $str === 'HTML' || $str === 'IMAGE' ||
			$str === 'IMG_RESOURCE' || $str === 'IMGTEXT' || $str === 'LOAD_REGISTER' || $str === 'MEDIA' ||
			$str === 'MULTIMEDIA' || $str === 'OTABLE' || $str === 'QTOBJECT' || $str === 'RECORDS' ||
			$str === 'RESTORE_REGISTER' || $str === 'SEARCHRESULT' || $str === 'SVG' || $str === 'SWFOBJECT' ||
			$str === 'TEMPLATE' || $str === 'TEXT' || $str === 'USER' || $str === 'USER_INT'
		);
	}

	public function getPreparedClassName($settingsArray, $defaultClassName = '') {
		$className = $defaultClassName;
		if(is_array($settingsArray) && $settingsArray['class']) {
			$className = $this->getSingle($settingsArray, 'class');
		}
		return $this->prepareClassName($className);
	}

	/**
	 * Redirects to a specified page or URL.
	 *
	 * @param mixed $redirect Page id or URL to redirect to
	 * @param boolean $correctRedirectUrl replace &amp; with & in URL 
	 * @return void
	 */
	public function doRedirect($redirect, $correctRedirectUrl, $additionalParams = array(), $headerStatusCode = '') {

		// these parameters have to be added to the redirect url
		$addParams = array();
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L')) {
			$addParams['L'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');
		}

		if (is_array($additionalParams)) {
			foreach ($additionalParams as $param=>$value) {
				if (FALSE === strpos($param, '.')) {
					if (is_array($additionalParams[$param . '.'])) {
						$value = $this->getSingle($additionalParams, $param);
					}
					$addParams[$param] = $value;
				}
			}
		}

		$url = $this->globals->getCObj()->getTypoLink_URL($redirect, $addParams);

		//correct the URL by replacing &amp;
		if ($correctRedirectUrl) {
			$url = str_replace('&amp;', '&', $url);
		}

		if ($url) {
			if(!$this->globals->isAjaxMode()) {
				$status = '303 See Other';
				if($headerStatusCode) {
					$status = $headerStatusCode;
				}
				header('Status: ' . $status);
				header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url));
			} else {
				print '{' . json_encode('redirect') . ':' . json_encode(\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url)) . '}';
				exit;
			}
			
		}
	}

	/**
	 * Redirects to a specified page or URL.
	 * The redirect url, additional params and other settings are taken from the given settings array.
	 *
	 * @param array $settings Array containing the redirect settings
	 * @param array $gp Array with GET/POST parameters
	 * @param string $redirectPageSetting Name of the Typoscript setting which holds the redirect page.
	 * @return void
	 */
	public function doRedirectBasedOnSettings($settings, $gp, $redirectPageSetting = 'redirectPage') {
		$redirectPage = $this->getSingle($settings, $redirectPageSetting);

		//Allow "redirectPage" to be the value of a form field
		if($redirectPage && isset($gp[$redirectPage])) {
			$redirectPage = $gp[$redirectPage];
		}

		if(strlen($redirectPage) > 0) {
			$correctRedirectUrl = $this->getSingle($settings, 'correctRedirectUrl');
			$headerStatusCode = $this->getSingle($settings, 'headerStatusCode');
			if(isset($settings['additionalParams']) && isset($settings['additionalParams.'])) {
				$additionalParamsString = $this->getSingle($settings, 'additionalParams');
				$additionalParamsKeysAndValues = explode('&', $additionalParamsString);
				$additionalParams = array();
				foreach($additionalParamsKeysAndValues as $keyAndValue) {
					list($key, $value) = explode('=', $keyAndValue, 2);
					$additionalParams[$key] = $value;
				}
			} else {
				$additionalParams = $settings['additionalParams.'];
			}
			$this->doRedirect($redirectPage, $correctRedirectUrl, $additionalParams, $headerStatusCode);
			exit();
		} else {
			$this->debugMessage('No redirectPage set.');
		}
	}

	/**
	 * Return value from somewhere inside a FlexForm structure
	 *
	 * @param	array		FlexForm data
	 * @param	string		Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
	 * @param	string		Sheet pointer, eg. "sDEF"
	 * @param	string		Language pointer, eg. "lDEF"
	 * @param	string		Value pointer, eg. "vDEF"
	 * @return	string		The content.
	 */
	public function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet  ='sDEF', $lang = 'lDEF', $value = 'vDEF') {
		$sheetArray = '';
		if (is_array($T3FlexForm_array)) {
			$sheetArray = $T3FlexForm_array['data'][$sheet][$lang];
		} else {
			$sheetArray = '';
		}
		if (is_array($sheetArray))	{
			return $this->pi_getFFvalueFromSheetArray($sheetArray, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('/', $fieldName), $value);
		}
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	public function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value) {
		$tempArr = $sheetArray;
		foreach ($fieldNameArr as $k => $v) {
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($v)) {
				if (is_array($tempArr)) {
					$c = 0;
					foreach ($tempArr as $idx => $values) {
						if ($c == $v) {
							$tempArr = $values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}

	/**
	 * Converts a date to a UNIX timestamp.
	 *
	 * @param array $options The TS settings of the "special" section
	 * @return long The timestamp
	*/
	public function dateToTimestamp($date, $format = 'Y-m-d') {
		if(strlen(trim($date)) > 0) {
			if(version_compare(PHP_VERSION, '5.3.0') < 0) {

				// find out separator
				preg_match('/^[d|m|y]*(.)[d|m|y]*/i', $format, $res);
				$sep = $res[1];

				// normalisation of format
				$pattern = $this->utilityFuncs->normalizeDatePattern($format, $sep);

				// find out correct positioins of "d","m","y"
				$pos1 = strpos($pattern, 'd');
				$pos2 = strpos($pattern, 'm');
				$pos3 = strpos($pattern, 'y');

				$dateParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sep, $date);
				$timestamp = mktime(0, 0, 0, $dateParts[$pos2], $dateParts[$pos1], $dateParts[$pos3]);
			} else {
				$dateObj = \DateTime::createFromFormat($format, $date);
				if($dateObj) {
					$timestamp = $dateObj->getTimestamp();
				} else {
					$this->debugMessage('Error parsing the date. Supported formats: http://www.php.net/manual/en/datetime.createfromformat.php', array(), 3, array('format' => $format, 'date' => $date));
					$timestamp = 0;
				}
			}
		}
		return $timestamp;
	}

	/**
	 * Ensures that a given path has a / as first and last character.
	 * This method only appends a / to the end of the path, if no filename is in path.
	 *
	 * Examples:
	 *
	 * uploads/temp				--> /uploads/temp/
	 * uploads/temp/file.ext	--> /uploads/temp/file.ext
	 *
	 * @param string $path
	 * @return string Sanitized path
	 */
	public function sanitizePath($path) {
		if (substr($path, 0, 1) !== '/' && substr($path, 1, 2) !== ':/') {
			$path = '/' . $path;
		}
		if (substr($path, (strlen($path) - 1)) !== '/' && !strstr($path, '.')) {
			$path = $path . '/';
		}
		while(strstr($path, '//')) {
			$path = str_replace('//', '/', $path);
		}
		return $path;
	}

	public function generateHash() {
		$result = '';
		$charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
		for($p = 0; $p < 15; $p++) {
			$result .= $charPool[mt_rand(0, strlen($charPool) - 1)];
		}
		return sha1(md5(sha1($result)));
	}

	/**
	 * Converts an absolute path into a relative path from TYPO3 root directory.
	 *
	 * Example:
	 *
	 * IN : C:/xampp/htdocs/typo3/fileadmin/file.html
	 * OUT : fileadmin/file.html
	 *
	 * @param string $template The template code
	 * @param string $langFile The path to the language file
	 * @return array The filled language markers
	 */
	public function convertToRelativePath($absPath) {

		//C:/xampp/htdocs/typo3/index.php
		$scriptPath =  \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_FILENAME');

		//C:/xampp/htdocs/typo3/
		$rootPath = str_replace('index.php', '', $scriptPath);

		return str_replace($rootPath, '', $absPath);
	}

	/**
	 * Finds and fills language markers in given template code.
	 *
	 * @param string $template The template code
	 * @param string $langFile The path to the language file
	 * @return array The filled language markers
	 */
	public function getFilledLangMarkers(&$template,$langFiles) {
		$langMarkers = array();
		if (is_array($langFiles)) {
			$aLLMarkerList = array();
			preg_match_all('/###LLL:.+?###/Ssm', $template, $aLLMarkerList);

			foreach ($aLLMarkerList[0] as $idx => $LLMarker){
				$llKey =  substr($LLMarker, 7, strlen($LLMarker) - 10);
				$marker = $llKey;
				$message = '';
				foreach ($langFiles as $idx => $langFile) {
					$message = trim($GLOBALS['TSFE']->sL('LLL:' . $langFile . ':' . $llKey));
				}
				$langMarkers['###LLL:' . $marker . '###'] = $message;
			}
		}
		return $langMarkers;
	}

	/**
	 * Method to log a debug message.
	 * The message will be handled by one or more configured "Debuggers".
	 *
	 * @param string $key The message or key in language file (locallang_debug.xml)
	 * @param array $printfArgs If the messsage contains placeholders for usage with printf, pass the replacement values in this array.
	 * @param int $severity The severity of the message. Valid values are 1,2 and 3 (1= info, 2 = warning, 3 = error)
	 * @param array $data Additional debug data (e.g. the array of GET/POST values)
	 * @return void
	 */
	public function debugMessage($key, array $printfArgs = array(), $severity = 1, array $data = array()) {
		$severity = intval($severity);
		$message = $this->getDebugMessage($key);
		if (strlen($message) == 0) {
			$message = $key;
		} elseif (count($printfArgs) > 0) {
			$message = vsprintf($message, $printfArgs);
		}
		$data = $this->recursiveHtmlSpecialChars($data);
		foreach($this->globals->getDebuggers() as $idx => $debugger) {
			$debugger->addToDebugLog(htmlspecialchars($message), $severity, $data);
		}
	}

	public function debugMailContent($emailObj) {
		$this->debugMessage('mail_subject', array($emailObj->getSubject()));

		$sender = $emailObj->getSender();
		if(!is_array($sender)) {
			$sender = array($sender);
		}
		$this->debugMessage('mail_sender', array(), 1, $sender);

		$replyTo = $emailObj->getReplyTo();
		if(!is_array($replyTo)) {
			$replyTo = array($replyTo);
		}
		$this->debugMessage('mail_replyto', array(), 1, $replyTo);

		$this->debugMessage('mail_cc', array(), 1, (array)$emailObj->getCc());
		$this->debugMessage('mail_bcc', array(), 1, (array)$emailObj->getBcc());
		$this->debugMessage('mail_returnpath', array(), 1, array($emailObj->returnPath));
		$this->debugMessage('mail_plain', array(), 1, array($emailObj->getPlain()));
		$this->debugMessage('mail_html', array(), 1, array($emailObj->getHTML()));
	}

	/**
	 * Manages the exception throwing
	 *
	 * @param string $key Key in language file
	 * @return void
	 */
	public function throwException($key) {
		$message = $this->getExceptionMessage($key);
		if (strlen($message) == 0) {
			throw new \Exception($key);
		} else {
			if (func_num_args() > 1) {
				$args = func_get_args();
				array_shift($args);
				$message = vsprintf($message, $args);
			}
			throw new \Exception($message);
		}
	}

	/**
	 * Removes unfilled markers from given template code.
	 *
	 * @param string $content The template code
	 * @return string The template code without markers
	 */
	public function removeUnfilledMarkers($content) {
		return preg_replace('/###.*?###/', '', $content);
	}

	/**
	 * Substitutes EXT: with extension path in a file path
	 *
	 * @param string The path
	 * @return string The resolved path
	 */
	public function resolvePath($path) {
		$path = explode('/', $path);
		if (strpos($path[0], 'EXT') === 0) {
			$parts = explode(':', $path[0]);
			$path[0] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($parts[1]);
		}
		$path = implode('/', $path);
		$path = str_replace('//', '/', $path);
		return $path;
	}

	/**
	 * Substitutes EXT: with extension path in a file path and returns the relative path.
	 *
	 * @param string The path
	 * @return string The resolved path
	 */
	public function resolveRelPath($path) {
		$path = explode('/', $path);
		if (strpos($path[0], 'EXT') === 0) {
			$parts = explode(':', $path[0]);
			$path[0] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($parts[1]);
		}
		$path = implode('/', $path);
		$path = str_replace('//', '/', $path);
		return $path;
	}

	/**
	 * Substitutes EXT: with extension path in a file path and returns the relative path from site root.
	 *
	 * @param string The path
	 * @return string The resolved path
	 */
	public function resolveRelPathFromSiteRoot($path) {
		if(substr($path, 0, 7) === 'http://') {
			return $path;
		}
		$path = explode('/', $path);
		if (strpos($path[0], 'EXT') === 0) {
			$parts = explode(':', $path[0]);
			$path[0] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($parts[1]);
		}
		$path = implode('/', $path);
		$path = str_replace('//', '/', $path);
		$path = str_replace('../', '', $path);
		return $path;
	}

	/**
	 * Searches for upload folder settings in TypoScript setup.
	 * If no settings is found, the default upload folder is set.
	 *
	 * Here is an example:
	 * <code>
	 * plugin.Tx_Formhandler.settings.files.tmpUploadFolder = uploads/formhandler/tmp
	 * </code>
	 *
	 * The default upload folder is: '/uploads/formhandler/tmp/'
	 *
	 * @return string
	 */
	public function getTempUploadFolder($fieldName = '') {

		//set default upload folder
		$uploadFolder = '/uploads/formhandler/tmp/';

		//if temp upload folder set in TypoScript, take that setting
		$settings = $this->globals->getSession()->get('settings');
		if(strlen($fieldName) > 0 && $settings['files.']['uploadFolder.'][$fieldName]) {
			$uploadFolder = $this->getSingle($settings['files.']['uploadFolder.'], $fieldName);
		} elseif($settings['files.']['uploadFolder.']['default']) {
			$uploadFolder = $this->getSingle($settings['files.']['uploadFolder.'], 'default');
		} elseif ($settings['files.']['uploadFolder']) {
			$uploadFolder = $this->getSingle($settings['files.'], 'uploadFolder');
		}

		$uploadFolder = $this->sanitizePath($uploadFolder);

		//if the set directory doesn't exist, print a message and try to create
		if (!is_dir($this->getTYPO3Root() . $uploadFolder)) {
			$this->debugMessage('folder_doesnt_exist', array($this->getTYPO3Root() . '/' . $uploadFolder), 2);
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($this->getTYPO3Root() . '/', $uploadFolder);
		}
		return $uploadFolder;
	}

	/**
	 * Searches for upload folders set in TypoScript setup.
	 * Returns all upload folders as array.
	 *
	 * @return array
	 */
	public function getAllTempUploadFolders() {

		$uploadFolders = array();

		//set default upload folder
		$defaultUploadFolder = '/uploads/formhandler/tmp/';

		//if temp upload folder set in TypoScript, take that setting
		$settings = $this->globals->getSession()->get('settings');

		if(is_array($settings['files.']['uploadFolder.'])) {
			foreach($settings['files.']['uploadFolder.'] as $fieldName => $folderSettings) {
				$uploadFolders[] = $this->sanitizePath($this->getSingle($settings['files.']['uploadFolder.'], $fieldName));
			}
		} elseif ($settings['files.']['uploadFolder']) {
			$defaultUploadFolder = $this->sanitizePath($this->getSingle($settings['files.'], 'uploadFolder'));
		}

		//If no special upload folder for a field was set, add the default upload folder
		if(count($uploadFolders) === 0) {
			$uploadFolders[] = $defaultUploadFolder;
		}
		return $uploadFolders;
	}

	/**
	 * Parses given value and unit and creates a timestamp now-timebase.
	 *
	 * @param int Timebase value
	 * @param string Timebase unit (seconds|minutes|hours|days)
	 * @return long The timestamp
	 */
	public function getTimestamp($value, $unit) {
		$now = time();
		$convertedValue = 0;
		switch ($unit) {
			case 'days':
				$convertedValue = $value * 24 * 60 * 60;
				break;
			case 'hours':
				$convertedValue = $value * 60 * 60;
				break;
			case 'minutes':
				$convertedValue = $value * 60;
				break;
			case 'seconds':
				$convertedValue = $value;
				break;
			default:
				$convertedValue = $value;
				break;
		}
		return $now - $convertedValue;
	}

	/**
	 * Parses given value and unit and returns the seconds.
	 *
	 * @param int Timebase value
	 * @param string Timebase unit (seconds|minutes|hours|days)
	 * @return long The seconds
	 */
	public function convertToSeconds($value,$unit) {
		$convertedValue = 0;
		switch ($unit) {
			case 'days':
				$convertedValue = $value * 24 * 60 * 60;
				break;
			case 'hours':
				$convertedValue = $value * 60 * 60;
				break;
			case 'minutes':
				$convertedValue = $value * 60;
				break;
			case 'seconds':
				$convertedValue = $value;
				break;
		}
		return $convertedValue;
	}

	public function generateRandomID() {
		$randomID = md5($this->globals->getFormValuesPrefix() . \TYPO3\CMS\Core\Utility\GeneralUtility::generateRandomBytes(10));
		return $randomID;
	}

	public function initializeTSFE($pid) {
		// create object instances:
		$GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, TRUE);
		$GLOBALS['TSFE']->tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\TemplateService');
		$GLOBALS['TSFE']->tmpl->init();

			// then initialize fe user
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->fe_user->fetchGroupData();

			// Include the TCA
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();

			// Get the page
		$GLOBALS['TSFE']->fetch_the_id();
		$GLOBALS['TSFE']->getConfigArray();
		if(is_array($GLOBALS['TSFE']->tmpl->setup['includeLibs.'])) {
			$GLOBALS['TSFE']->includeLibraries($GLOBALS['TSFE']->tmpl->setup['includeLibs.']);
		}
		$GLOBALS['TSFE']->settingLanguage();
		$GLOBALS['TSFE']->newCObj();
	}
	
	/**
	 * Returns a debug message according to given key
	 *
	 * @param string The key in translation file
	 * @return string
	 */
	public function getDebugMessage($key) {
		return trim($GLOBALS['TSFE']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_debug.xml:' . $key));
	}

	/**
	 * Returns an exception message according to given key
	 *
	 * @param string The key in translation file
	 * @return string
	 */
	public function getExceptionMessage($key) {
		return trim($GLOBALS['TSFE']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_exceptions.xml:' . $key));
	}
	
	/**
	 * Performs search and replace settings defined in TypoScript.
	 * 
	 * Example:
	 * 
	 * <code>
	 * plugin.Tx_Formhandler.settings.files.search = ä,ö,ü
	 * plugin.Tx_Formhandler.settings.files.replace = ae,oe,ue 
	 * </code>
	 *
	 * @param string The file name
	 * @return string The replaced file name
	 *
	 **/
	public function doFileNameReplace($fileName) {

		$settings = $this->globals->getSettings();

		//Default: Replace spaces with underscores
		$search = array(' ', '%20');
		$replace = array('_');
		$separator = ',';

		$usePregReplace = $this->getSingle($settings['files.'], 'usePregReplace');
		if(intval($usePregReplace) === 1) {
			$search = array('/ /', '/%20/');
		}

		//The settings "search" and "replace" are comma separated lists
		if($settings['files.']['search']) {
			$search = $this->getSingle($settings['files.'], 'search');
			if($settings['files.']['search.']['separator']) {
				$separator = $this->getSingle($settings['files.']['search.'], 'separator');
			}
			$search = explode($separator, $search);
		}
		if($settings['files.']['replace']) {
			$replace = $this->getSingle($settings['files.'], 'replace');
			if($settings['files.']['replace.']['separator']) {
				$separator = $this->getSingle($settings['files.']['replace.'], 'separator');
			}
			$replace = explode($separator, $replace);
		}

		$usePregReplace = $this->getSingle($settings['files.'], 'usePregReplace');
		if(intval($usePregReplace) === 1) {
			$fileName = preg_replace($search, $replace, $fileName);
		} else {
			$fileName = str_replace($search, $replace, $fileName);
		}
		return $fileName;
	}

	public function recursiveHtmlSpecialChars($values) {
		if(is_array($values)) {
			foreach($values as &$value) {
				if(is_array($value)) {
					$value = $this->recursiveHtmlSpecialChars($value);
				} else {
					$value = htmlspecialchars($value);
				}
			}
		} else {
			$values = htmlspecialchars($values);
		}
		return $values;
	}

	/**
	 * Convert a shorthand byte value from a PHP configuration directive to an integer value
	 * 
	 * Copied from http://www.php.net/manual/de/faq.using.php#78405
	 * 
	 * @param	string	$value
	 * @return	int
	 */
	public function convertBytes($value) {
		if(is_numeric($value)) {
			return $value;
		} else {
			$value_length = strlen($value);
			$qty = substr($value, 0, $value_length - 1);
			$unit = strtolower(substr($value, $value_length - 1));
			switch($unit) {
				case 'k':
					$qty *= 1024;
					break;
				case 'm':
					$qty *= 1048576;
					break;
				case 'g':
					$qty *= 1073741824;
					break;
			}
			return $qty;
		}
	}

	/**
	 * Check if a given string is a file path or contains parsed HTML template data
	 *
	 * @param	string	$templateFile
	 * @return	boolean
	 */
	public function isTemplateFilePath($templateFile) {
		return (stristr($templateFile, '###TEMPLATE_') === FALSE);
	}

	/**
	 * Method to normalize a specified date pattern for internal use
	 *
	 * @param string $pattern The pattern
	 * @param string $sep The separator character
	 * @return string The normalized pattern
	 */
	public function normalizeDatePattern($pattern, $sep = '') {
		$pattern = strtoupper($pattern);
		$pattern = str_replace(
			array($sep, 'DD', 'D', 'MM', 'M', 'YYYY', 'YY', 'Y'),
			array('', 'd', 'd', 'm', 'm', 'y', 'y', 'y'),
			$pattern
		);
		return $pattern;
	}

	/**
	 * Copy of tslib_content::getGlobal for use in Formhandler.
	 * 
	 * Changed to be able to return an array and not only scalar values.
	 * 
	 * @param string Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
	 * @param array Alternative array than $GLOBAL to get variables from.
	 * @return mixed Whatever value. If none, then blank string.
	 */
	public function getGlobal($keyString, $source = NULL) {
		$keys = explode('|', $keyString);
		$numberOfLevels = count($keys);
		$rootKey = trim($keys[0]);
		$value = isset($source) ? $source[$rootKey] : $GLOBALS[$rootKey];

		for ($i = 1; $i < $numberOfLevels && isset($value); $i++) {
			$currentKey = trim($keys[$i]);
			if (is_object($value)) {
				$value = $value->$currentKey;
			} elseif (is_array($value)) {
				$value = $value[$currentKey];
			} else {
				$value = '';
				break;
			}
		}

		if($value === NULL) {
			$value = '';
		}
		return $value;
	}

	public function wrap($str, $settingsArray, $key) {
		$wrappedString = $str;
		$this->globals->getCObj()->setCurrentVal($wrappedString);
		if(is_array($settingsArray[$key . '.'])) {
			$wrappedString = $this->globals->getCObj()->stdWrap($str, $settingsArray[$key . '.']);
		} elseif(strlen($settingsArray[$key]) > 0) {
			$wrappedString = $this->globals->getCObj()->wrap($str, $settingsArray[$key]);
		}
		return $wrappedString;
	}

	public function getAjaxUrl($specialParams) {
		$params = array(
			'id' => $GLOBALS['TSFE']->id,
			'L' => $GLOBALS['TSFE']->sys_language_uid,
			'randomID' => $this->globals->getRandomID(),
			'field' => $field,
			'uploadedFileName' => $uploadedFileName
		);
		$params = array_merge($params, $specialParams);
		return \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'index.php?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $params);
	}

	public function prepareAndWhereString($andWhere) {
		$andWhere = trim($andWhere);
		if(substr($andWhere, 0, 3) === 'AND') {
			$andWhere = trim(substr($andWhere, 3));
		}
		if(strlen($andWhere) > 0) {
			$andWhere = ' AND ' . $andWhere;
		}
		return $andWhere;
	}

	/**
	 * Interprets a string. If it starts with a { like {field:fieldname}
	 * it calls TYPO3 getData function and returns its value, otherwise returns the string
	 *
	 * @param string $operand The operand to be interpreted
	 * @param array $values The GET/POST values
	 * @return string
	 */
	public function parseOperand($operand, $values) {
		$returnValue = '';
		if ($operand[0] == '{') {
			$data = trim($operand, '{}');
			$returnValue = $this->globals->getcObj()->getData($data, $values);
		} else {
			$returnValue = $operand;
		}
		if($returnValue === NULL) {
			$returnValue = '';
		}
		return $returnValue;
	}

	/**
	 * Merges 2 configuration arrays
	 *
	 * @param array The base settings
	 * @param array The settings overriding the base settings.
	 * @return array The merged settings
	 */
	public function mergeConfiguration($settings, $newSettings) {
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($settings, $newSettings);
		return $settings;
	}

	public function parseResourceFiles($settings, $key) {
		$resourceFile = $settings[$key];
		$resourceFiles = array();
		if (!$this->isValidCObject($resourceFile) && $settings[$key . '.']) {
			foreach ($settings[$key . '.'] as $idx => $file) {
				if(strpos($idx, '.') === FALSE) {
					$file = $this->getSingle($settings[$key . '.'], $idx);
					$fileOptions = $settings[$key . '.'][$idx . '.'];
					$fileOptions['file'] = $file;
					$resourceFiles[] = $fileOptions;
				}
			}
		} else {
			$fileOptions = array('file' => $resourceFile);
			$resourceFiles[] = $fileOptions;
		}
		return $resourceFiles;
	}

	public function getConditionResult($condition, $gp) {
		$valueConditions = preg_split('/\s*(!=|\^=|\$=|~=|>=|<=|=|<|>)\s*/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);

		$conditionOperator = trim($valueConditions[1]);
		$fieldName = trim($valueConditions[0]);
		$conditionResult = FALSE;
		switch($conditionOperator) {
			case '!=':
				$value = $this->parseOperand($valueConditions[2], $gp);
				$conditionResult = $this->getGlobal($fieldName, $gp) != $value;
				break;
			case '^=':
				$value = $this->parseOperand($valueConditions[2], $gp);
				$conditionResult = strpos($this->getGlobal($fieldName, $gp), $value) === 0;
				break;
			case '$=':
				$gpValue = $this->getGlobal($fieldName, $gp);
				$gpValue = substr($gpValue, -strlen($valueConditions[2]));
				$checkValue = $this->parseOperand($valueConditions[2], $gp);
				$conditionResult = (strcmp($checkValue, $gpValue) === 0);
				break;
			case '~=':
				$value = $this->parseOperand($valueConditions[2], $gp);
				$gpValue = $this->getGlobal($fieldName, $gp);
				if(is_array($gpValue)) {
					$conditionResult = in_array($value, $gpValue);
				} else {
					$conditionResult = strpos($this->getGlobal($fieldName, $gp), $value) !== FALSE;
				}
				break;
			case '=':
				$value = $this->parseOperand($valueConditions[2], $gp);
				$conditionResult = $this->getGlobal($fieldName, $gp) == $value;
				break;
			case '>':
				$value = $this->getGlobal($fieldName, $gp);
				if(is_numeric($value)) {
					$conditionResult = floatval($value) > floatval($this->parseOperand($valueConditions[2], $gp));
				}
				break;
			case '<':
				$value = $this->getGlobal($fieldName, $gp);
				if(is_numeric($value)) {
					$conditionResult = floatval($value) < floatval($this->parseOperand($valueConditions[2], $gp));
				}
				break;
			case '>=':
				$value = $this->getGlobal($fieldName, $gp);
				if(is_numeric($value)) {
					$conditionResult = floatval($value) >= floatval($this->parseOperand($valueConditions[2], $gp));
				}
				break;
			case '<=':
				$value = $this->getGlobal($fieldName, $gp);
				if(is_numeric($value)) {
					$conditionResult = floatval($value) <= floatval($this->parseOperand($valueConditions[2], $gp));
				}
				break;
			default:
				$value = $this->getGlobal($fieldName, $gp);
				if(is_array($value)) {
					$conditionResult = (count($value) > 0);
				} else {
					$conditionResult = strlen(trim($value)) > 0;
				}
		}

		return $conditionResult;
	}
}
