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
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A class providing helper functions for Formhandler
 */
class GeneralUtility implements SingletonInterface
{

    /**
     * Returns the absolute path to the document root
     *
     * @return string
     */
    public static function getDocumentRoot()
    {
        return PATH_site;
    }

    public static function getMergedGP()
    {
        $gp = array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST());
        $prefix = \Typoheads\Formhandler\Utility\Globals::getFormValuesPrefix();
        if ($prefix) {
            if (is_array($gp[$prefix])) {
                $gp = $gp[$prefix];
            } else {
                $gp = [];
            }
        }

        /*
         * Unset key "saveDB" to prevent conflicts with information set by Finisher_DB
         */
        if (is_array($gp) && array_key_exists('saveDB', $gp)) {
            unset($gp['saveDB']);
        }
        return $gp;
    }

    /**
     * Returns the absolute path to the TYPO3 root
     *
     * @return string
     */
    public static function getTYPO3Root()
    {
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
    public static function prepareClassName($className)
    {
        $className = ltrim($className, '\\');
        $className = str_replace('Tx_Formhandler_', 'Typoheads\\Formhandler\\', $className);
        if (strstr($className, '_') !== false && (strstr($className, 'Typoheads\\Formhandler\\') !== false || substr_count($className, '_') === 1)) {
            $className = str_replace('_', '\\', $className);
        }
        if (substr_count($className, '\\') === 1 && substr($className, 0, 11) !== '\\Typoheads\\') {
            $className = 'Typoheads\\Formhandler\\' . $className;
        }
        if ($className === 'Typoheads\\Formhandler\\Validator\\Default') {
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
     * @param    string
     * @param    array
     * @return    string
     */
    public static function substituteMarkerArray($content, $markContentArray)
    {
        if (is_array($markContentArray)) {
            reset($markContentArray);
            foreach ($markContentArray as $marker => $markContent) {
                $content = str_replace($marker, $markContent, $content);
            }
        }
        return $content;
    }

    /**
     * Returns the first subpart encapsulated in the marker, $marker (possibly present in $content as a HTML comment)
     *
     * @param    string    Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
     * @param    string    Marker string, eg. "###CONTENT_PART###"
     * @return    string
     */
    public static function getSubpart($content, $marker)
    {
        $start = strpos($content, $marker);
        if ($start === false) {
            return '';
        }
        $start += strlen($marker);
        $stop = strpos($content, $marker, $start);
        $content = substr($content, $start, ($stop - $start));
        $matches = [];
        if (preg_match('/^([^\<]*\-\-\>)(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches) === 1) {
            return $matches[2];
        }
        $matches = [];
        if (preg_match('/(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches) === 1) {
            return $matches[1];
        }
        $matches = [];
        if (preg_match('/^([^\<]*\-\-\>)(.*)$/s', $content, $matches) === 1) {
            return $matches[2];
        }
        return $content;
    }

    /**
     * Read template file set in flexform or TypoScript, read the file's contents to $this->templateFile
     *
     * @param $settings The formhandler settings
     * @return string
     */
    public static function readTemplateFile($templateFile, &$settings)
    {

        //template file was not set in flexform, search TypoScript for setting
        if (!$templateFile) {
            if (!$settings['templateFile'] && !$settings['templateFile.']) {
                return '';
            }
            $templateFile = $settings['templateFile'];

            if (isset($settings['templateFile.']) && is_array($settings['templateFile.'])) {
                $templateFile = self::getSingle($settings, 'templateFile');
                if (self::isTemplateFilePath($templateFile)) {
                    $templateFile = self::resolvePath($templateFile);
                    if (!@file_exists($templateFile)) {
                        self::throwException('template_file_not_found', $templateFile);
                    }
                    $templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile);
                } else {

                    //The setting "templateFile" was a cObject which returned HTML content. Just use that as template code.
                    $templateCode = $templateFile;
                }
            } else {
                $templateFile = self::resolvePath($templateFile);
                if (!@file_exists($templateFile)) {
                    self::throwException('template_file_not_found', $templateFile);
                }
                $templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile);
            }
        } else {
            if (self::isTemplateFilePath($templateFile)) {
                $templateFile = self::resolvePath($templateFile);
                if (!@file_exists($templateFile)) {
                    self::throwException('template_file_not_found', $templateFile);
                }
                $templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile);
            } else {
                // given variable $templateFile already contains the template code
                $templateCode = $templateFile;
            }
        }
        if (strlen($templateCode) === 0) {
            self::throwException('empty_template_file', $templateFile);
        }
        if (stristr($templateCode, '###TEMPLATE_') === false) {
            self::throwException('invalid_template_file', $templateFile);
        }
        return $templateCode;
    }

    /**
     * Read language file set in flexform or TypoScript, read the file's path to $this->langFile
     *
     * @param array $langFiles
     * @param array $settings
     * @return array
     */
    public static function readLanguageFiles($langFiles, &$settings)
    {

        //language file was not set in flexform, search TypoScript for setting
        if (!$langFiles) {
            $langFiles = [];
            if (isset($settings['langFile']) && !isset($settings['langFile.'])) {
                array_push($langFiles, self::resolveRelPathFromSiteRoot($settings['langFile']));
            } elseif (isset($settings['langFile']) && isset($settings['langFile.'])) {
                array_push($langFiles, self::getSingle($settings, 'langFile'));
            } elseif (isset($settings['langFile.']) && is_array($settings['langFile.'])) {
                foreach ($settings['langFile.'] as $key => $langFile) {
                    if (false === strpos($key, '.')) {
                        if (is_array($settings['langFile.'][$key . '.'])) {
                            array_push($langFiles, self::getSingle($settings['langFile.'], $key));
                        } else {
                            array_push($langFiles, self::resolveRelPathFromSiteRoot($langFile));
                        }
                    }
                }
            }
        }
        foreach ($langFiles as $idx => &$langFile) {
            $langFile = self::convertToRelativePath($langFile);
        }
        return $langFiles;
    }

    public static function getTranslatedMessage($langFiles, $key)
    {
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

    public static function getSingle($arr, $key)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        if (!is_array($arr[$key . '.'])) {
            return $arr[$key];
        }
        if (!isset($arr[$key . '.']['sanitize'])) {
            $arr[$key . '.']['sanitize'] = 1;
        }
        if (!self::isValidCObject($arr[$key])) {
            return $arr[$key];
        }
        return \Typoheads\Formhandler\Utility\Globals::getCObj()->cObjGetSingle($arr[$key], $arr[$key . '.']);
    }

    public static function isValidCObject($str)
    {
        return
            $str === 'CASE' || $str === 'CLEARGIF' || $str === 'COA' || $str === 'COA_INT' ||
            $str === 'COLUMNS' || $str === 'CONTENT' || $str === 'CTABLE' || $str === 'EDITPANEL' ||
            $str === 'FILE' || $str === 'FILES' || $str === 'FLUIDTEMPLATE' || $str === 'FORM' ||
            $str === 'HMENU' || $str === 'HRULER' || $str === 'HTML' || $str === 'IMAGE' ||
            $str === 'IMG_RESOURCE' || $str === 'IMGTEXT' || $str === 'LOAD_REGISTER' || $str === 'MEDIA' ||
            $str === 'MULTIMEDIA' || $str === 'OTABLE' || $str === 'QTOBJECT' || $str === 'RECORDS' ||
            $str === 'RESTORE_REGISTER' || $str === 'SEARCHRESULT' || $str === 'SVG' || $str === 'SWFOBJECT' ||
            $str === 'TEMPLATE' || $str === 'TEXT' || $str === 'USER' || $str === 'USER_INT'
        ;
    }

    public static function getPreparedClassName($settingsArray, $defaultClassName = '')
    {
        $className = $defaultClassName;
        if (is_array($settingsArray) && $settingsArray['class']) {
            $className = self::getSingle($settingsArray, 'class');
        }
        return self::prepareClassName($className);
    }

    /**
     * Redirects to a specified page or URL.
     *
     * @param mixed $redirect Page id or URL to redirect to
     * @param bool $correctRedirectUrl replace &amp; with & in URL
     */
    public static function doRedirect($redirect, $correctRedirectUrl, $additionalParams = [], $headerStatusCode = '')
    {

        // these parameters have to be added to the redirect url
        $addParams = [];
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L')) {
            $addParams['L'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');
        }

        if (is_array($additionalParams)) {
            foreach ($additionalParams as $param => $value) {
                if (false === strpos($param, '.')) {
                    if (is_array($additionalParams[$param . '.'])) {
                        $value = self::getSingle($additionalParams, $param);
                    }
                    $addParams[$param] = $value;
                }
            }
        }

        $url = \Typoheads\Formhandler\Utility\Globals::getCObj()->getTypoLink_URL($redirect, $addParams);

        //correct the URL by replacing &amp;
        if ($correctRedirectUrl) {
            $url = str_replace('&amp;', '&', $url);
        }

        if ($url) {
            if (!\Typoheads\Formhandler\Utility\Globals::isAjaxMode()) {
                $status = '303 See Other';
                if ($headerStatusCode) {
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
     */
    public static function doRedirectBasedOnSettings($settings, $gp, $redirectPageSetting = 'redirectPage')
    {
        $redirectPage = self::getSingle($settings, $redirectPageSetting);

        //Allow "redirectPage" to be the value of a form field
        if ($redirectPage && isset($gp[$redirectPage])) {
            $redirectPage = $gp[$redirectPage];
        }

        if (strlen($redirectPage) > 0) {
            $correctRedirectUrl = self::getSingle($settings, 'correctRedirectUrl');
            $headerStatusCode = self::getSingle($settings, 'headerStatusCode');
            if (isset($settings['additionalParams']) && isset($settings['additionalParams.'])) {
                $additionalParamsString = self::getSingle($settings, 'additionalParams');
                $additionalParamsKeysAndValues = explode('&', $additionalParamsString);
                $additionalParams = [];
                foreach ($additionalParamsKeysAndValues as $keyAndValue) {
                    list($key, $value) = explode('=', $keyAndValue, 2);
                    $additionalParams[$key] = $value;
                }
            } else {
                $additionalParams = $settings['additionalParams.'];
            }
            self::doRedirect($redirectPage, $correctRedirectUrl, $additionalParams, $headerStatusCode);
            exit();
        }
        self::debugMessage('No redirectPage set.');
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param    array        FlexForm data
     * @param    string        Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param    string        Sheet pointer, eg. "sDEF"
     * @param    string        Language pointer, eg. "lDEF"
     * @param    string        Value pointer, eg. "vDEF"
     * @return    string        The content.
     */
    public static function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF')
    {
        if (is_array($T3FlexForm_array)) {
            $sheetArray = $T3FlexForm_array['data'][$sheet][$lang];
        } else {
            $sheetArray = '';
        }
        if (is_array($sheetArray)) {
            return self::pi_getFFvalueFromSheetArray($sheetArray, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('/', $fieldName), $value);
        }
        return '';
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param    array        Multidimensiona array, typically FlexForm contents
     * @param    array        Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
     * @param    string        Value for outermost key, typ. "vDEF" depending on language.
     * @return    mixed        The value, typ. string.
     * @see pi_getFFvalue()
     */
    public static function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
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
    public static function dateToTimestamp($date, $format = 'Y-m-d')
    {
        if (strlen(trim($date)) > 0) {
            if (version_compare(PHP_VERSION, '5.3.0') < 0) {

                // find out separator
                preg_match('/^[d|m|y]*(.)[d|m|y]*/i', $format, $res);
                $sep = $res[1];

                // normalisation of format
                $pattern = self::normalizeDatePattern($format, $sep);

                // find out correct positioins of "d","m","y"
                $pos1 = strpos($pattern, 'd');
                $pos2 = strpos($pattern, 'm');
                $pos3 = strpos($pattern, 'y');

                $dateParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sep, $date);
                $timestamp = mktime(0, 0, 0, $dateParts[$pos2], $dateParts[$pos1], $dateParts[$pos3]);
            } else {
                $dateObj = \DateTime::createFromFormat($format, $date);
                if ($dateObj) {
                    $timestamp = $dateObj->getTimestamp();
                } else {
                    self::debugMessage('Error parsing the date. Supported formats: http://www.php.net/manual/en/datetime.createfromformat.php', [], 3, ['format' => $format, 'date' => $date]);
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
     * uploads/temp                --> /uploads/temp/
     * uploads/temp/file.ext    --> /uploads/temp/file.ext
     *
     * @param string $path
     * @return string Sanitized path
     */
    public static function sanitizePath($path)
    {
        if (substr($path, 0, 1) !== '/' && substr($path, 1, 2) !== ':/') {
            $path = '/' . $path;
        }
        if (substr($path, (strlen($path) - 1)) !== '/' && !strstr($path, '.')) {
            $path = $path . '/';
        }
        while (strstr($path, '//')) {
            $path = str_replace('//', '/', $path);
        }
        return $path;
    }

    public static function generateHash()
    {
        $result = '';
        $charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($p = 0; $p < 15; $p++) {
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
    public static function convertToRelativePath($absPath)
    {

        //C:/xampp/htdocs/typo3/index.php
        $scriptPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_FILENAME');

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
    public static function getFilledLangMarkers(&$template, $langFiles)
    {
        $langMarkers = [];
        if (is_array($langFiles)) {
            $aLLMarkerList = [];
            preg_match_all('/###LLL:.+?###/Ssm', $template, $aLLMarkerList);

            foreach ($aLLMarkerList[0] as $idx => $LLMarker) {
                $llKey = substr($LLMarker, 7, strlen($LLMarker) - 10);
                $marker = $llKey;
                $message = '';
                foreach ($langFiles as $langFile) {
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
     */
    public static function debugMessage($key, array $printfArgs = [], $severity = 1, array $data = [])
    {
        $severity = intval($severity);
        $message = self::getDebugMessage($key);
        if (strlen($message) == 0) {
            $message = $key;
        } elseif (count($printfArgs) > 0) {
            $message = vsprintf($message, $printfArgs);
        }
        $data = self::recursiveHtmlSpecialChars($data);
        foreach (\Typoheads\Formhandler\Utility\Globals::getDebuggers() as $idx => $debugger) {
            $debugger->addToDebugLog(htmlspecialchars($message), $severity, $data);
        }
    }

    public static function debugMailContent($emailObj)
    {
        self::debugMessage('mail_subject', [$emailObj->getSubject()]);

        $sender = $emailObj->getSender();
        if (!is_array($sender)) {
            $sender = [$sender];
        }
        self::debugMessage('mail_sender', [], 1, $sender);

        $replyTo = $emailObj->getReplyTo();
        if (!is_array($replyTo)) {
            $replyTo = [$replyTo];
        }
        self::debugMessage('mail_replyto', [], 1, $replyTo);

        self::debugMessage('mail_cc', [], 1, (array)$emailObj->getCc());
        self::debugMessage('mail_bcc', [], 1, (array)$emailObj->getBcc());
        self::debugMessage('mail_returnpath', [], 1, [$emailObj->returnPath]);
        self::debugMessage('mail_plain', [], 1, [$emailObj->getPlain()]);
        self::debugMessage('mail_html', [], 1, [$emailObj->getHTML()]);
    }

    /**
     * Manages the exception throwing
     *
     * @param string $key Key in language file
     */
    public static function throwException($key)
    {
        $message = self::getExceptionMessage($key);
        if (strlen($message) == 0) {
            throw new \Exception($key);
        }
        if (func_num_args() > 1) {
            $args = func_get_args();
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        throw new \Exception($message);
    }

    /**
     * Removes unfilled markers from given template code.
     *
     * @param string $content The template code
     * @return string The template code without markers
     */
    public static function removeUnfilledMarkers($content)
    {
        return preg_replace('/###.*?###/', '', $content);
    }

    /**
     * Substitutes EXT: with extension path in a file path
     *
     * @param string The path
     * @return string The resolved path
     */
    public static function resolvePath($path)
    {
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
    public static function resolveRelPath($path)
    {
        $path = explode('/', $path);
        if (strpos($path[0], 'EXT') === 0) {
            $parts = explode(':', $path[0]);
            $path[0] = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($parts[1]));
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
    public static function resolveRelPathFromSiteRoot($path)
    {
        if (substr($path, 0, 7) === 'http://') {
            return $path;
        }
        $path = explode('/', $path);
        if (strpos($path[0], 'EXT') === 0) {
            $parts = explode(':', $path[0]);
            $path[0] = PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($parts[1]));
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
    public static function getTempUploadFolder($fieldName = '')
    {

        //set default upload folder
        $uploadFolder = '/uploads/formhandler/tmp/';

        //if temp upload folder set in TypoScript, take that setting
        $settings = \Typoheads\Formhandler\Utility\Globals::getSession()->get('settings');
        if (strlen($fieldName) > 0 && $settings['files.']['uploadFolder.'][$fieldName]) {
            $uploadFolder = self::getSingle($settings['files.']['uploadFolder.'], $fieldName);
        } elseif ($settings['files.']['uploadFolder.']['default']) {
            $uploadFolder = self::getSingle($settings['files.']['uploadFolder.'], 'default');
        } elseif ($settings['files.']['uploadFolder']) {
            $uploadFolder = self::getSingle($settings['files.'], 'uploadFolder');
        }

        $uploadFolder = self::sanitizePath($uploadFolder);

        //if the set directory doesn't exist, print a message and try to create
        if (!is_dir(self::getTYPO3Root() . $uploadFolder)) {
            self::debugMessage('folder_doesnt_exist', [self::getTYPO3Root() . '/' . $uploadFolder], 2);
            \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep(self::getTYPO3Root() . '/', $uploadFolder);
        }
        return $uploadFolder;
    }

    /**
     * Searches for upload folders set in TypoScript setup.
     * Returns all upload folders as array.
     *
     * @return array
     */
    public static function getAllTempUploadFolders()
    {
        $uploadFolders = [];

        //set default upload folder
        $defaultUploadFolder = '/uploads/formhandler/tmp/';

        //if temp upload folder set in TypoScript, take that setting
        $settings = \Typoheads\Formhandler\Utility\Globals::getSession()->get('settings');

        if (is_array($settings['files.']['uploadFolder.'])) {
            foreach ($settings['files.']['uploadFolder.'] as $fieldName => $folderSettings) {
                $uploadFolders[] = self::sanitizePath(self::getSingle($settings['files.']['uploadFolder.'], $fieldName));
            }
        } elseif ($settings['files.']['uploadFolder']) {
            $defaultUploadFolder = self::sanitizePath(self::getSingle($settings['files.'], 'uploadFolder'));
        }

        //If no special upload folder for a field was set, add the default upload folder
        if (count($uploadFolders) === 0) {
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
    public static function getTimestamp($value, $unit)
    {
        $now = time();
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
    public static function convertToSeconds($value, $unit)
    {
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

    public static function generateRandomID()
    {
        $randomID = md5(
            \Typoheads\Formhandler\Utility\Globals::getFormValuesPrefix() .
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Random::class)->generateRandomBytes(10)
        );
        return $randomID;
    }

    public static function initializeTSFE($pid)
    {
        // create object instances:
        $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], $pid, 0, true);
        $GLOBALS['TSFE']->tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\TemplateService');
        $GLOBALS['TSFE']->tmpl->init();

        // then initialize fe user
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->fe_user->fetchGroupData();

        // Include the TCA
        \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();

        // Get the page
        $GLOBALS['TSFE']->fetch_the_id();
        $GLOBALS['TSFE']->getConfigArray();
        if (is_array($GLOBALS['TSFE']->tmpl->setup['includeLibs.'])) {
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
    public static function getDebugMessage($key)
    {
        return trim($GLOBALS['TSFE']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_debug.xml:' . $key));
    }

    /**
     * Returns an exception message according to given key
     *
     * @param string The key in translation file
     * @return string
     */
    public static function getExceptionMessage($key)
    {
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
    public static function doFileNameReplace($fileName)
    {
        $settings = \Typoheads\Formhandler\Utility\Globals::getSettings();

        //Default: Replace spaces with underscores
        $search = [' ', '%20'];
        $replace = ['_'];
        $separator = ',';

        $usePregReplace = self::getSingle($settings['files.'], 'usePregReplace');
        if (intval($usePregReplace) === 1) {
            $search = ['/ /', '/%20/'];
        }

        //The settings "search" and "replace" are comma separated lists
        if ($settings['files.']['search']) {
            $search = self::getSingle($settings['files.'], 'search');
            if ($settings['files.']['search.']['separator']) {
                $separator = self::getSingle($settings['files.']['search.'], 'separator');
            }
            $search = explode($separator, $search);
        }
        if ($settings['files.']['replace']) {
            $replace = self::getSingle($settings['files.'], 'replace');
            if ($settings['files.']['replace.']['separator']) {
                $separator = self::getSingle($settings['files.']['replace.'], 'separator');
            }
            $replace = explode($separator, $replace);
        }

        $usePregReplace = self::getSingle($settings['files.'], 'usePregReplace');
        if (intval($usePregReplace) === 1) {
            $fileName = preg_replace($search, $replace, $fileName);
        } else {
            $fileName = str_replace($search, $replace, $fileName);
        }
        return $fileName;
    }

    public static function recursiveHtmlSpecialChars($values)
    {
        if (is_array($values)) {
            foreach ($values as &$value) {
                if (is_array($value)) {
                    $value = self::recursiveHtmlSpecialChars($value);
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
     * @param    string $value
     * @return    int
     */
    public static function convertBytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        $value_length = strlen($value);
        $qty = substr($value, 0, $value_length - 1);
        $unit = strtolower(substr($value, $value_length - 1));
        switch ($unit) {
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

    /**
     * Check if a given string is a file path or contains parsed HTML template data
     *
     * @param    string $templateFile
     * @return    bool
     */
    public static function isTemplateFilePath($templateFile)
    {
        return stristr($templateFile, '###TEMPLATE_') === false;
    }

    /**
     * Method to normalize a specified date pattern for internal use
     *
     * @param string $pattern The pattern
     * @param string $sep The separator character
     * @return string The normalized pattern
     */
    public static function normalizeDatePattern($pattern, $sep = '')
    {
        $pattern = strtoupper($pattern);
        $pattern = str_replace(
            [$sep, 'DD', 'D', 'MM', 'M', 'YYYY', 'YY', 'Y'],
            ['', 'd', 'd', 'm', 'm', 'y', 'y', 'y'],
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
    public static function getGlobal($keyString, $source = null)
    {
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

        if ($value === null) {
            $value = '';
        }
        return $value;
    }

    public static function wrap($str, $settingsArray, $key)
    {
        $wrappedString = $str;
        \Typoheads\Formhandler\Utility\Globals::getCObj()->setCurrentVal($wrappedString);
        if (is_array($settingsArray[$key . '.'])) {
            $wrappedString = \Typoheads\Formhandler\Utility\Globals::getCObj()->stdWrap($str, $settingsArray[$key . '.']);
        } elseif (strlen($settingsArray[$key]) > 0) {
            $wrappedString = \Typoheads\Formhandler\Utility\Globals::getCObj()->wrap($str, $settingsArray[$key]);
        }
        return $wrappedString;
    }

    public static function getAjaxUrl($specialParams)
    {
        $params = [
            'id' => $GLOBALS['TSFE']->id,
            'L' => $GLOBALS['TSFE']->sys_language_uid,
            'randomID' => \Typoheads\Formhandler\Utility\Globals::getRandomID(),
            'field' => $field,
            'uploadedFileName' => $uploadedFileName
        ];
        $params = array_merge($params, $specialParams);
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'index.php?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $params);
    }

    public static function prepareAndWhereString($andWhere)
    {
        $andWhere = trim($andWhere);
        if (substr($andWhere, 0, 3) === 'AND') {
            $andWhere = trim(substr($andWhere, 3));
        }
        if (strlen($andWhere) > 0) {
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
    public static function parseOperand($operand, $values)
    {
        if ($operand[0] == '{') {
            $data = trim($operand, '{}');
            $returnValue = \Typoheads\Formhandler\Utility\Globals::getcObj()->getData($data, $values);
        } else {
            $returnValue = $operand;
        }
        if ($returnValue === null) {
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
    public static function mergeConfiguration($settings, $newSettings)
    {
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($settings, $newSettings);
        return $settings;
    }

    public static function parseResourceFiles($settings, $key)
    {
        $resourceFile = $settings[$key];
        $resourceFiles = [];
        if (!self::isValidCObject($resourceFile) && $settings[$key . '.']) {
            foreach ($settings[$key . '.'] as $idx => $file) {
                if (strpos($idx, '.') === false) {
                    $file = self::getSingle($settings[$key . '.'], $idx);
                    $fileOptions = $settings[$key . '.'][$idx . '.'];
                    $fileOptions['file'] = $file;
                    $resourceFiles[] = $fileOptions;
                }
            }
        } else {
            $fileOptions = ['file' => $resourceFile];
            $resourceFiles[] = $fileOptions;
        }
        return $resourceFiles;
    }

    public static function getConditionResult($condition, $gp)
    {
        $valueConditions = preg_split('/\s*(!=|\^=|\$=|~=|>=|<=|=|<|>)\s*/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);

        $conditionOperator = trim($valueConditions[1]);
        $fieldName = trim($valueConditions[0]);
        $conditionResult = false;
        switch ($conditionOperator) {
            case '!=':
                $value = self::parseOperand($valueConditions[2], $gp);
                $conditionResult = self::getGlobal($fieldName, $gp) != $value;
                break;
            case '^=':
                $value = self::parseOperand($valueConditions[2], $gp);
                $conditionResult = strpos(self::getGlobal($fieldName, $gp), $value) === 0;
                break;
            case '$=':
                $gpValue = self::getGlobal($fieldName, $gp);
                $gpValue = substr($gpValue, -strlen($valueConditions[2]));
                $checkValue = self::parseOperand($valueConditions[2], $gp);
                $conditionResult = (strcmp($checkValue, $gpValue) === 0);
                break;
            case '~=':
                $value = self::parseOperand($valueConditions[2], $gp);
                $gpValue = self::getGlobal($fieldName, $gp);
                if (is_array($gpValue)) {
                    $conditionResult = in_array($value, $gpValue);
                } else {
                    $conditionResult = strpos(self::getGlobal($fieldName, $gp), $value) !== false;
                }
                break;
            case '=':
                $value = self::parseOperand($valueConditions[2], $gp);
                $conditionResult = self::getGlobal($fieldName, $gp) == $value;
                break;
            case '>':
                $value = self::getGlobal($fieldName, $gp);
                if (is_numeric($value)) {
                    $conditionResult = floatval($value) > floatval(self::parseOperand($valueConditions[2], $gp));
                }
                break;
            case '<':
                $value = self::getGlobal($fieldName, $gp);
                if (is_numeric($value)) {
                    $conditionResult = floatval($value) < floatval(self::parseOperand($valueConditions[2], $gp));
                }
                break;
            case '>=':
                $value = self::getGlobal($fieldName, $gp);
                if (is_numeric($value)) {
                    $conditionResult = floatval($value) >= floatval(self::parseOperand($valueConditions[2], $gp));
                }
                break;
            case '<=':
                $value = self::getGlobal($fieldName, $gp);
                if (is_numeric($value)) {
                    $conditionResult = floatval($value) <= floatval(self::parseOperand($valueConditions[2], $gp));
                }
                break;
            default:
                $value = self::getGlobal($fieldName, $gp);
                if (is_array($value)) {
                    $conditionResult = (count($value) > 0);
                } else {
                    $conditionResult = strlen(trim($value)) > 0;
                }
        }

        return $conditionResult;
    }

    /**
     * Modifies a HTML Hex color by adding/subtracting $R,$G and $B integers
     *
     * @param string $color A hexadecimal color code, #xxxxxx
     * @param int $R Offset value 0-255
     * @param int $G Offset value 0-255
     * @param int $B Offset value 0-255
     * @return string A hexadecimal color code, #xxxxxx, modified according to input vars
     */
    public static function modifyHTMLColor($color, $R, $G, $B)
    {
        // This takes a hex-color (# included!) and adds $R, $G and $B to the HTML-color (format: #xxxxxx) and returns the new color
        $nR = MathUtility::forceIntegerInRange(hexdec(substr($color, 1, 2)) + $R, 0, 255);
        $nG = MathUtility::forceIntegerInRange(hexdec(substr($color, 3, 2)) + $G, 0, 255);
        $nB = MathUtility::forceIntegerInRange(hexdec(substr($color, 5, 2)) + $B, 0, 255);
        return '#' . substr(('0' . dechex($nR)), -2) . substr(('0' . dechex($nG)), -2) . substr(('0' . dechex($nB)), -2);
    }
}
