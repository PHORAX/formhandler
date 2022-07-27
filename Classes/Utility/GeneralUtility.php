<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Utility;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Typoheads\Formhandler\Mailer\TYPO3Mailer;

/**
 * This script is part of the TYPO3 project - inspiring people to share!
 *
 * TYPO3 is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by
 * the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 */

/**
 * A class providing helper functions for Formhandler.
 */
class GeneralUtility implements SingletonInterface {
  /**
   * Convert a shorthand byte value from a PHP configuration directive to an integer value.
   *
   * Copied from http://www.php.net/manual/de/faq.using.php#78405
   */
  public static function convertBytes(string $value): int {
    if (is_numeric($value)) {
      return (int) $value;
    }
    $value_length = strlen($value);
    $qty = intval(substr($value, 0, $value_length - 1));
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
   * Converts an absolute path into a relative path from TYPO3 root directory.
   *
   * Example:
   *
   * IN : C:/xampp/htdocs/typo3/fileadmin/file.html
   * OUT : fileadmin/file.html
   *
   * @param string $absPath The absolute path
   *
   * @return string The relative path
   */
  public static function convertToRelativePath(string $absPath): string {
    // C:/xampp/htdocs/typo3/index.php
    $scriptPath = strval(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_FILENAME'));

    // C:/xampp/htdocs/typo3/
    $rootPath = str_replace('index.php', '', $scriptPath);

    return str_replace($rootPath, '', $absPath);
  }

  /**
   * Parses given value and unit and returns the seconds.
   *
   * @param int    $value Timebase value
   * @param string $unit  Timebase unit (seconds|minutes|hours|days)
   *
   * @return int The seconds
   */
  public static function convertToSeconds(int $value, string $unit): int {
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

  /**
   * Converts a date to a UNIX timestamp.
   *
   * @param string $date   The date to convert
   * @param string $format The format the date has
   *
   * @return int The timestamp
   */
  public static function dateToTimestamp(string $date, string $format = 'Y-m-d'): int {
    $timestamp = 0;
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
        $timestamp = mktime(0, 0, 0, intval($dateParts[$pos2]), intval($dateParts[$pos1]), intval($dateParts[$pos3])) ?: 0;
      } else {
        $dateObj = \DateTime::createFromFormat($format, $date);
        if ($dateObj) {
          $timestamp = $dateObj->getTimestamp();
        } else {
          self::debugMessage('Error parsing the date. Supported formats: http://www.php.net/manual/en/datetime.createfromformat.php', [], 3, ['format' => $format, 'date' => $date]);
        }
      }
    }

    return $timestamp;
  }

  public static function debugMailContent(TYPO3Mailer $emailObj): void {
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

    self::debugMessage('mail_cc', [], 1, $emailObj->getCc());
    self::debugMessage('mail_bcc', [], 1, $emailObj->getBcc());
    self::debugMessage('mail_returnpath', [], 1, [$emailObj->getReturnPath() ?? '']);
    self::debugMessage('mail_plain', [], 1, [$emailObj->getPlain()]);
    self::debugMessage('mail_html', [], 1, [$emailObj->getHTML()]);
  }

  /**
   * Method to log a debug message.
   * The message will be handled by one or more configured "Debuggers".
   *
   * @param string                   $key        The message or key in language file (locallang_debug.xlf)
   * @param array<int, mixed>        $printfArgs if the messsage contains placeholders for usage with printf, pass the replacement values in this array
   * @param int                      $severity   The severity of the message. Valid values are 1,2 and 3 (1= info, 2 = warning, 3 = error)
   * @param array<int|string, mixed> $data       Additional debug data (e.g. the array of GET/POST values)
   */
  public static function debugMessage(string $key, array $printfArgs = [], int $severity = 1, array $data = []): void {
    $severity = (int) $severity;
    $message = self::getDebugMessage($key);
    if (0 == strlen($message)) {
      $message = $key;
    } elseif (count($printfArgs) > 0) {
      $message = vsprintf($message, $printfArgs);
    }
    $data = self::recursiveHtmlSpecialChars($data);
    foreach (Globals::getDebuggers() as $idx => $debugger) {
      $debugger->addToDebugLog(htmlspecialchars($message), $severity, [$data]);
    }
  }

  /**
   * Performs search and replace settings defined in TypoScript.
   *
   * Example:
   *
   * <code>
   * plugin.tx_formhandler_pi1.settings.files.search = ä,ö,ü
   * plugin.tx_formhandler_pi1.settings.files.replace = ae,oe,ue
   * </code>
   *
   * @param string $fileName The file name
   *
   * @return string The replaced file name
   */
  public static function doFileNameReplace(string $fileName): string {
    $settings = Globals::getSettings();

    // Default: Replace spaces with underscores
    $search = [' ', '%20'];
    $replace = ['_'];
    $separator = ',';

    $files = (array) ($settings['files.'] ?? []);
    $usePregReplace = intval(self::getSingle($files, 'usePregReplace'));
    if (1 == $usePregReplace) {
      $search = ['/ /', '/%20/'];
    }

    // The settings "search" and "replace" are comma separated lists
    if (isset($files['search'])) {
      $search = self::getSingle((array) $files, 'search');
      if (isset($files['search.']) && is_array($files['search.']) && isset($files['search.']['separator'])) {
        $separatorTemp = self::getSingle($files['search.'], 'separator');
      }
      $search = explode(!empty($separatorTemp) ? $separatorTemp : $separator, $search);
    }
    if (isset($files['replace'])) {
      $replace = self::getSingle((array) $files, 'replace');
      if (isset($files['replace.']) && is_array($files['replace.']) && isset($files['replace.']['separator'])) {
        $separatorTemp = self::getSingle($files['replace.'], 'separator');
      }
      $replace = explode(!empty($separatorTemp) ? $separatorTemp : $separator, $replace);
    }

    $usePregReplace = self::getSingle($files, 'usePregReplace');
    if (1 == $usePregReplace) {
      $fileName = preg_replace($search, $replace, $fileName);
    } else {
      $fileName = str_replace($search, $replace, $fileName);
    }

    return $fileName ?? '';
  }

  /**
   * Redirects to a specified page or URL.
   *
   * @param string               $redirect           Page id or URL to redirect to
   * @param bool                 $correctRedirectUrl replace &amp; with & in URL
   * @param array<string, mixed> $additionalParams
   */
  public static function doRedirect(string $redirect, bool $correctRedirectUrl, array $additionalParams = [], string $headerStatusCode = ''): void {
    // these parameters have to be added to the redirect url
    $addParams = [];
    if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L')) {
      $addParams['L'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');
    }

    if (is_array($additionalParams)) {
      foreach ($additionalParams as $param => $value) {
        if (false === strpos($param, '.')) {
          if (is_array($additionalParams[$param.'.'])) {
            $value = self::getSingle($additionalParams, $param);
          }
          $addParams[$param] = $value;
        }
      }
    }

    $url = Globals::getCObj()?->getTypoLink_URL($redirect, $addParams) ?? '';

    // correct the URL by replacing &amp;
    if ($correctRedirectUrl) {
      $url = str_replace('&amp;', '&', $url);
    }

    if ($url) {
      if (!Globals::isAjaxMode()) {
        $status = '303 See Other';
        if ($headerStatusCode) {
          $status = $headerStatusCode;
        }
        header('Status: '.$status);
        header('Location: '.\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url));
      } else {
        echo '{'.json_encode('redirect').':'.json_encode(\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url)).'}';

        exit;
      }
    }
  }

  /**
   * Redirects to a specified page or URL.
   * The redirect url, additional params and other settings are taken from the given settings array.
   *
   * @param array<string, mixed> $settings            Array containing the redirect settings
   * @param array<string, mixed> $gp                  Array with GET/POST parameters
   * @param string               $redirectPageSetting name of the Typoscript setting which holds the redirect page
   */
  public static function doRedirectBasedOnSettings(array $settings, array $gp, string $redirectPageSetting = 'redirectPage'): void {
    $redirectPage = self::getSingle($settings, $redirectPageSetting);

    // Allow "redirectPage" to be the value of a form field
    if ($redirectPage && isset($gp[$redirectPage])) {
      $redirectPage = strval($gp[$redirectPage]);
    }

    if (strlen($redirectPage) > 0) {
      $correctRedirectUrl = (bool) self::getSingle($settings, 'correctRedirectUrl');
      $headerStatusCode = (string) self::getSingle($settings, 'headerStatusCode');
      if (isset($settings['additionalParams'], $settings['additionalParams.'])) {
        $additionalParamsString = self::getSingle($settings, 'additionalParams');
        $additionalParamsKeysAndValues = explode('&', $additionalParamsString);
        $additionalParams = [];
        foreach ($additionalParamsKeysAndValues as $keyAndValue) {
          list($key, $value) = explode('=', $keyAndValue, 2);
          $additionalParams[$key] = $value;
        }
      } else {
        $additionalParams = (array) ($settings['additionalParams.'] ?? []);
      }
      self::doRedirect($redirectPage, $correctRedirectUrl, $additionalParams, $headerStatusCode);

      exit;
    }
    self::debugMessage('No redirectPage set.');
  }

  public static function generateHash(): string {
    $result = '';
    $charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
    for ($p = 0; $p < 15; ++$p) {
      $result .= $charPool[mt_rand(0, strlen($charPool) - 1)];
    }

    return sha1(md5(sha1($result)));
  }

  public static function generateRandomID(): string {
    /** @var Random $random */
    $random = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Random::class);

    return md5(
      Globals::getFormValuesPrefix().
      $random->generateRandomBytes(10)
    );
  }

  /**
   * @param array<string, mixed> $specialParams
   */
  public static function getAjaxUrl(string $path, array $specialParams): string {
    /** @var Context $context */
    $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Context::class);
    $params = [
      'id' => $GLOBALS['TSFE']->id,
      'L' => $context->getPropertyFromAspect('language', 'id'),
      'randomID' => Globals::getRandomID(),
    ];
    $params = array_merge($params, $specialParams);

    return strval(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH')).$path.'?'.\TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $params);
  }

  /**
   * Searches for upload folders set in TypoScript setup.
   * Returns all upload folders as array.
   *
   * @return string[]
   */
  public static function getAllTempUploadFolders(): array {
    $uploadFolders = [];

    // set default upload folder
    $defaultUploadFolder = '/uploads/formhandler/tmp/';

    // if temp upload folder set in TypoScript, take that setting
    $settings = (array) (Globals::getSession()?->get('settings') ?? []);

    $files = (array) ($settings['files.'] ?? []);
    if (isset($files['uploadFolder.']) && is_array($files['uploadFolder.'])) {
      foreach ($files['uploadFolder.'] as $fieldName => $folderSettings) {
        $uploadFolders[] = self::sanitizePath(self::getSingle($files['uploadFolder.'], $fieldName));
      }
    } elseif (isset($files['uploadFolder'])) {
      $defaultUploadFolder = self::sanitizePath(self::getSingle((array) $files, 'uploadFolder'));
    }

    // If no special upload folder for a field was set, add the default upload folder
    if (0 === count($uploadFolders)) {
      $uploadFolders[] = $defaultUploadFolder;
    }

    return $uploadFolders;
  }

  /**
   * @param array<string, mixed> $gp Array with GET/POST parameters
   */
  public static function getConditionResult(string $condition, array $gp): bool {
    $conditionResult = false;
    $valueConditions = preg_split('/\s*(!=|\^=|\$=|~=|>=|<=|=|<|>)\s*/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (is_bool($valueConditions)) {
      return $conditionResult;
    }

    $conditionOperator = trim($valueConditions[1]);
    $fieldName = trim($valueConditions[0]);

    switch ($conditionOperator) {
      case '!=':
        $value = self::parseOperand($valueConditions[2], $gp);
        $conditionResult = self::getGlobal($fieldName, $gp) != $value;

        break;

      case '^=':
        $value = self::parseOperand($valueConditions[2], $gp);
        $conditionResult = 0 === strpos(strval(self::getGlobal($fieldName, $gp)), $value);

        break;

      case '$=':
        $gpValue = strval(self::getGlobal($fieldName, $gp));
        $gpValue = substr($gpValue, -strlen($valueConditions[2]));
        $checkValue = self::parseOperand($valueConditions[2], $gp);
        $conditionResult = (0 === strcmp($checkValue, $gpValue));

        break;

      case '~=':
        $value = self::parseOperand($valueConditions[2], $gp);
        $gpValue = self::getGlobal($fieldName, $gp);
        if (is_array($gpValue)) {
          $conditionResult = in_array($value, $gpValue);
        } else {
          $conditionResult = false !== strpos(strval(self::getGlobal($fieldName, $gp)), $value);
        }

        break;

      case '=':
        $value = self::parseOperand($valueConditions[2], $gp);
        $conditionResult = self::getGlobal($fieldName, $gp) == $value;

        break;

      case '>':
        $value = self::getGlobal($fieldName, $gp);
        if (is_numeric($value)) {
          $conditionResult = (float) $value > (float) self::parseOperand($valueConditions[2], $gp);
        }

        break;

      case '<':
        $value = self::getGlobal($fieldName, $gp);
        if (is_numeric($value)) {
          $conditionResult = (float) $value < (float) self::parseOperand($valueConditions[2], $gp);
        }

        break;

      case '>=':
        $value = self::getGlobal($fieldName, $gp);
        if (is_numeric($value)) {
          $conditionResult = (float) $value >= (float) self::parseOperand($valueConditions[2], $gp);
        }

        break;

      case '<=':
        $value = self::getGlobal($fieldName, $gp);
        if (is_numeric($value)) {
          $conditionResult = (float) $value <= (float) self::parseOperand($valueConditions[2], $gp);
        }

        break;

      default:
        $value = self::getGlobal($fieldName, $gp);
        if (is_array($value)) {
          $conditionResult = (count($value) > 0);
        } else {
          $conditionResult = strlen(trim(strval($value))) > 0;
        }
    }

    return $conditionResult;
  }

  /**
   * Returns a debug message according to given key.
   *
   * @param string $key The key in translation file
   */
  public static function getDebugMessage(string $key): string {
    return trim(LocalizationUtility::translate('LLL:EXT:formhandler/Resources/Private/Language/locallang_debug.xlf:'.$key) ?? '');
  }

  /**
   * Returns the absolute path to the document root.
   */
  public static function getDocumentRoot(): string {
    return Environment::getPublicPath().'/';
  }

  /**
   * Returns an exception message according to given key.
   *
   * @param string $key The key in translation file
   */
  public static function getExceptionMessage(string $key): string {
    return trim(LocalizationUtility::translate('LLL:EXT:formhandler/Resources/Private/Language/locallang_exceptions.xlf:'.$key) ?? '');
  }

  /**
   * Finds and fills language markers in given template code.
   *
   * @param string   &$template The template code
   * @param string[] $langFiles The path to the language file
   *
   * @return array<string, string> The filled language markers
   */
  public static function getFilledLangMarkers(string &$template, array $langFiles): array {
    $langMarkers = [];
    if (is_array($langFiles)) {
      $aLLMarkerList = [];
      preg_match_all('/###LLL:.+?###/Ssm', $template, $aLLMarkerList);

      foreach ($aLLMarkerList[0] as $LLMarker) {
        $llKey = substr($LLMarker, 7, strlen($LLMarker) - 10);
        $marker = $llKey;
        $message = '';
        foreach ($langFiles as $langFile) {
          $message = trim(LocalizationUtility::translate('LLL:'.$langFile.':'.$llKey) ?? '');
        }
        $langMarkers['###LLL:'.$marker.'###'] = $message;
      }
    }

    return $langMarkers;
  }

  /**
   * Copy of tslib_content::getGlobal for use in Formhandler.
   *
   * Changed to be able to return an array and not only scalar values.
   *
   * @param string               $keyString Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
   * @param array<string, mixed> $source    alternative array than $GLOBAL to get variables from
   *
   * @return mixed Whatever value. If none, then blank string.
   */
  public static function getGlobal(string $keyString, array $source = []): mixed {
    $keys = explode('|', $keyString);
    $numberOfLevels = count($keys);
    $rootKey = trim($keys[0]);
    $value = isset($source[$rootKey]) ? $source[$rootKey] : $GLOBALS[$rootKey] ?? '';

    for ($i = 1; $i < $numberOfLevels && isset($value); ++$i) {
      $currentKey = trim($keys[$i]);
      if (is_object($value)) {
        $value = $value->{$currentKey};
      } elseif (is_array($value)) {
        $value = $value[$currentKey];
      } else {
        $value = '';

        break;
      }
    }

    if (null === $value) {
      $value = '';
    }

    return $value;
  }

  /**
   * @return array<string, mixed>
   */
  public static function getMergedGP(): array {
    $gp = array_merge((array) \TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), (array) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST());
    $prefix = Globals::getFormValuesPrefix();
    if ($prefix) {
      if (isset($gp[$prefix])) {
        $gp = (array) $gp[$prefix];
      } else {
        $gp = [];
      }
    }

    // Unset key "saveDB" to prevent conflicts with information set by Finisher_DB
    if (is_array($gp) && array_key_exists('saveDB', $gp)) {
      unset($gp['saveDB']);
    }

    return $gp;
  }

  /**
   * @param array<string, mixed> $settingsArray
   *
   * @return class-string
   */
  public static function getPreparedClassName(array $settingsArray, string $defaultClassName = '') {
    $className = $defaultClassName;
    if (isset($settingsArray['class'])) {
      $className = self::getSingle($settingsArray, 'class');
    }

    return self::prepareClassName($className);
  }

  /**
   * @param array<string, mixed>|string $arr
   */
  public static function getSingle(array|string $arr, string|int $key): string {
    if (!is_array($arr)) {
      return $arr;
    }
    if (isset($arr[$key.'.']) && !is_array($arr[$key.'.'])) {
      return strval($arr[$key]);
    }
    if (isset($arr[$key.'.']) && is_array($arr[$key.'.']) && !isset($arr[$key.'.']['sanitize'])) {
      $arr[$key.'.']['sanitize'] = 1;
    }
    if (isset($arr[$key]) && !self::isValidCObject(strval($arr[$key]))) {
      return strval($arr[$key]);
    }
    if (!isset($arr[$key]) || !isset($arr[$key.'.'])) {
      return '';
    }

    return Globals::getCObj()?->cObjGetSingle(strval($arr[$key]), (array) $arr[$key.'.']) ?? '';
  }

  /**
   * Returns the first subpart encapsulated in the marker, $marker (possibly present in $content as a HTML comment).
   *
   * @param string $content Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
   * @param string $marker  Marker string, eg. "###CONTENT_PART###"
   */
  public static function getSubpart(string $content, string $marker): string {
    $start = strpos($content, $marker);
    if (false === $start) {
      return '';
    }
    $start += strlen($marker);
    $stop = strpos($content, $marker, $start);
    $content = substr($content, $start, $stop - $start);
    $matches = [];
    if (1 === preg_match('/^([^\<]*\-\-\>)(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches)) {
      return $matches[2];
    }
    $matches = [];
    if (1 === preg_match('/(.*)(\<\!\-\-[^\>]*)$/s', $content, $matches)) {
      return $matches[1];
    }
    $matches = [];
    if (1 === preg_match('/^([^\<]*\-\-\>)(.*)$/s', $content, $matches)) {
      return $matches[2];
    }

    return $content;
  }

  /**
   * Searches for upload folder settings in TypoScript setup.
   * If no settings is found, the default upload folder is set.
   *
   * Here is an example:
   * <code>
   * plugin.tx_formhandler_pi1.settings.files.tmpUploadFolder = uploads/formhandler/tmp
   * </code>
   *
   * The default upload folder is: '/uploads/formhandler/tmp/'
   */
  public static function getTempUploadFolder(string $fieldName = ''): string {
    // set default upload folder
    $uploadFolder = '/uploads/formhandler/tmp/';

    // if temp upload folder set in TypoScript, take that setting
    $settings = (array) (Globals::getSession()?->get('settings') ?? []);
    $files = (array) ($settings['files.'] ?? []);
    if (strlen($fieldName) > 0 && isset($files['uploadFolder.']) && is_array($files['uploadFolder.']) && isset($files['uploadFolder.'][$fieldName])) {
      $uploadFolder = self::getSingle($files['uploadFolder.'], $fieldName);
    } elseif (isset($files['uploadFolder.']) && is_array($files['uploadFolder.']) && isset($files['uploadFolder.']['default'])) {
      $uploadFolder = self::getSingle($files['uploadFolder.'], 'default');
    } elseif (isset($files['uploadFolder'])) {
      $uploadFolder = self::getSingle((array) $files, 'uploadFolder');
    }

    $uploadFolder = self::sanitizePath($uploadFolder);

    // if the set directory doesn't exist, print a message and try to create
    if (!is_dir(self::getTYPO3Root().$uploadFolder)) {
      self::debugMessage('folder_doesnt_exist', [self::getTYPO3Root().'/'.$uploadFolder], 2);
      \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep(self::getTYPO3Root().'/'.$uploadFolder);
    }

    return $uploadFolder;
  }

  /**
   * Parses given value and unit and creates a timestamp now-timebase.
   *
   * @param int    $value Timebase value
   * @param string $unit  Timebase unit (seconds|minutes|hours|days)
   *
   * @return int The timestamp
   */
  public static function getTimestamp(int $value, string $unit): int {
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
   * @param string[] $langFiles The formhandler lang files
   */
  public static function getTranslatedMessage(array $langFiles, string $key): string {
    $message = '';
    foreach ($langFiles as $langFile) {
      $messageTemp = trim(LocalizationUtility::translate('LLL:'.$langFile.':'.$key) ?? '');
      if (strlen($messageTemp) > 0) {
        $message = $messageTemp;
      }
    }

    return $message;
  }

  /**
   * Returns the absolute path to the TYPO3 root.
   */
  public static function getTYPO3Root(): string {
    return str_replace('/index.php', '', strval(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_FILENAME')));
  }

  public static function initializeTSFE(ServerRequestInterface $request): void {
    if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
      $site = $request->getAttribute('site');

      if (!$site instanceof SiteInterface) {
        /** @var SiteFinder $siteFinder */
        $siteFinder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();
        $site = reset($sites);
      }
      if (is_bool($site)) {
        return;
      }

      /** @var Context $context */
      $context = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Context::class);
      $context->setAspect('typoscript', new TypoScriptAspect(true));

      $language = $request->getAttribute('language') ?? $site->getDefaultLanguage();
      $queryParams = $request->getQueryParams();
      $parsedBody = (array) ($request->getParsedBody() ?? []);

      $pageId = intval($queryParams['id'] ?? $parsedBody['id'] ?? $site->getRootPageId());
      $pageType = strval($queryParams['type'] ?? $parsedBody['type'] ?? 0);
      $pageArguments = new PageArguments($pageId, $pageType, [], $queryParams);

      // create object instances:
      $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        TypoScriptFrontendController::class,
        $context,
        $site,
        $language,
        $pageArguments,
        $request->getAttribute('frontend.user') ?? \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(FrontendUserAuthentication::class)
      );

      $GLOBALS['TSFE']->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRepository::class, $context);
      $GLOBALS['TSFE']->tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TemplateService::class);
      $GLOBALS['TSFE']->id = $pageId;
      $GLOBALS['TSFE']->determineId($request);
      $GLOBALS['TSFE']->getFromCache($request);
      $GLOBALS['TSFE']->getConfigArray();
      $GLOBALS['TSFE']->newCObj($request);
    }
  }

  /**
   * Check if a given string is a file path or contains parsed HTML template data.
   */
  public static function isTemplateFilePath(string $templateFile): bool {
    return false === stristr($templateFile, '###TEMPLATE_');
  }

  public static function isValidCObject(string $str): bool {
    return
      'CASE' === $str || 'CLEARGIF' === $str || 'COA' === $str || 'COA_INT' === $str
      || 'COLUMNS' === $str || 'CONTENT' === $str || 'CTABLE' === $str || 'EDITPANEL' === $str
      || 'FILE' === $str || 'FILES' === $str || 'FLUIDTEMPLATE' === $str || 'FORM' === $str
      || 'HMENU' === $str || 'HRULER' === $str || 'HTML' === $str || 'IMAGE' === $str
      || 'IMG_RESOURCE' === $str || 'IMGTEXT' === $str || 'LOAD_REGISTER' === $str || 'MEDIA' === $str
      || 'MULTIMEDIA' === $str || 'OTABLE' === $str || 'QTOBJECT' === $str || 'RECORDS' === $str
      || 'RESTORE_REGISTER' === $str || 'SEARCHRESULT' === $str || 'SVG' === $str || 'SWFOBJECT' === $str
      || 'TEMPLATE' === $str || 'TEXT' === $str || 'USER' === $str || 'USER_INT' === $str
    ;
  }

  /**
   * Merges 2 configuration arrays.
   *
   * @param array<string, mixed> $settings    The base settings
   * @param array<string, mixed> $newSettings the settings overriding the base settings
   *
   * @return array<string, mixed> The merged settings
   */
  public static function mergeConfiguration(array $settings, array $newSettings): array {
    ArrayUtility::mergeRecursiveWithOverrule($settings, $newSettings);

    return $settings;
  }

  /**
   * Modifies a HTML Hex color by adding/subtracting $R,$G and $B integers.
   *
   * @param string $color A hexadecimal color code, #xxxxxx
   * @param int    $R     Offset value 0-255
   * @param int    $G     Offset value 0-255
   * @param int    $B     Offset value 0-255
   *
   * @return string A hexadecimal color code, #xxxxxx, modified according to input vars
   */
  public static function modifyHTMLColor(string $color, int $R, int $G, int $B): string {
    // This takes a hex-color (# included!) and adds $R, $G and $B to the HTML-color (format: #xxxxxx) and returns the new color
    $nR = MathUtility::forceIntegerInRange(intval(hexdec(substr($color, 1, 2)) + $R), 0, 255);
    $nG = MathUtility::forceIntegerInRange(intval(hexdec(substr($color, 3, 2)) + $G), 0, 255);
    $nB = MathUtility::forceIntegerInRange(intval(hexdec(substr($color, 5, 2)) + $B), 0, 255);

    return '#'.substr('0'.dechex($nR), -2).substr('0'.dechex($nG), -2).substr('0'.dechex($nB), -2);
  }

  /**
   * Method to normalize a specified date pattern for internal use.
   *
   * @param string $pattern The pattern
   * @param string $sep     The separator character
   *
   * @return string The normalized pattern
   */
  public static function normalizeDatePattern(string $pattern, string $sep = ''): string {
    $pattern = strtoupper($pattern);

    return str_replace(
      [$sep, 'DD', 'D', 'MM', 'M', 'YYYY', 'YY', 'Y'],
      ['', 'd', 'd', 'm', 'm', 'y', 'y', 'y'],
      $pattern
    );
  }

  /**
   * Method to parse a conditions block of the TS setting "if".
   *
   * @param array<string, mixed> $settings The settings of this form
   * @param array<string, mixed> $gp       The GET and POST vars
   *
   * @return array<string, mixed>
   */
  public static function parseConditionsBlock(array $settings, array $gp): array {
    if (!isset($settings['if.']) || !is_array($settings['if.'])) {
      return $settings;
    }
    foreach ($settings['if.'] as $idx => $conditionSettings) {
      $conditionSettings = is_array($conditionSettings) ? (array) $conditionSettings : [];
      $conditions = $conditionSettings['conditions.'] ?? [];
      $orConditions = [];
      foreach ($conditions as $subIdx => $andConditions) {
        $results = [];
        foreach ($andConditions as $subSubIdx => $andCondition) {
          $result = strval(self::getConditionResult($andCondition, $gp));
          $results[] = ($result ? 'TRUE' : 'FALSE');
        }
        $orConditions[] = '('.implode(' && ', $results).')';
      }
      $finalCondition = '('.implode(' || ', $orConditions).')';

      $evaluation = false;
      eval('$evaluation = '.$finalCondition.';');

      // @phpstan-ignore-next-line
      if ($evaluation) {
        $newSettings = $conditionSettings['isTrue.'] ?? '';
        if (is_array($newSettings)) {
          $settings = self::mergeConfiguration($settings, $newSettings);
        }
      } else {
        $newSettings = $conditionSettings['else.'] ?? '';
        if (is_array($newSettings)) {
          $settings = self::mergeConfiguration($settings, $newSettings);
        }
      }
    }

    return $settings;
  }

  /**
   * Interprets a string. If it starts with a { like {field:fieldname}
   * it calls TYPO3 getData function and returns its value, otherwise returns the string.
   *
   * @param string               $operand The operand to be interpreted
   * @param array<string, mixed> $values  The GET/POST values
   */
  public static function parseOperand(string $operand, array $values): string {
    if (!empty($operand) && '{' == $operand[0]) {
      $data = trim($operand, '{}');
      $returnValue = Globals::getcObj()?->getData($data, $values) ?? '';
    } else {
      $returnValue = $operand;
    }

    return $returnValue;
  }

  /**
   * @param array<string, mixed> $settings The formhandler settings
   *
   * @return mixed[]
   */
  public static function parseResourceFiles(array $settings, string $key): array {
    $resourceFile = strval($settings[$key] ?? '');
    $resourceFiles = [];
    if (!self::isValidCObject($resourceFile) && isset($settings[$key.'.']) && is_array($settings[$key.'.'])) {
      foreach ($settings[$key.'.'] as $idx => $file) {
        if (false === strpos($idx, '.')) {
          $file = self::getSingle($settings[$key.'.'], $idx);
          $fileOptions = $settings[$key.'.'][$idx.'.'];
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

  /**
   * Return value from somewhere inside a FlexForm structure.
   *
   * @param array<string, mixed> $T3FlexForm_array FlexForm data
   * @param string               $fieldName        Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
   * @param string               $sheet            Sheet pointer, eg. "sDEF"
   * @param string               $lang             Language pointer, eg. "lDEF"
   * @param string               $value            Value pointer, eg. "vDEF"
   *
   * @return string the content
   */
  public static function pi_getFFvalue(array $T3FlexForm_array, string $fieldName, string $sheet = 'sDEF', string $lang = 'lDEF', string $value = 'vDEF'): string {
    if (
      is_array($T3FlexForm_array) && isset($T3FlexForm_array['data'])
      && is_array($T3FlexForm_array['data']) && isset($T3FlexForm_array['data'][$sheet])
      && is_array($T3FlexForm_array['data'][$sheet]) && isset($T3FlexForm_array['data'][$sheet][$lang])
    ) {
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
   * Returns part of $sheetArray pointed to by the keys in $fieldNameArray.
   *
   * @param array<string, mixed> $sheetArray   Multidimensiona array, typically FlexForm contents
   * @param array<string, mixed> $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
   * @param string               $value        Value for outermost key, typ. "vDEF" depending on language.
   *
   * @return string The value, typ. string.
   *
   * @see pi_getFFvalue()
   */
  public static function pi_getFFvalueFromSheetArray(array $sheetArray, array $fieldNameArr, string $value): string {
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
            ++$c;
          }
        }
      } else {
        $tempArr = (array) ($tempArr[strval($v)] ?? []);
      }
    }

    return $tempArr[$value] ?? '';
  }

  public static function prepareAndWhereString(string $andWhere): string {
    $andWhere = trim($andWhere);
    if ('AND' === substr($andWhere, 0, 3)) {
      $andWhere = trim(substr($andWhere, 3));
    }
    if (strlen($andWhere) > 0) {
      $andWhere = ' AND '.$andWhere;
    }

    return $andWhere;
  }

  /**
   * Adds needed prefix to class name if not set in TS.
   *
   * @return class-string
   */
  public static function prepareClassName(string $className) {
    $className = ltrim($className, '\\');
    $className = str_replace('Tx_Formhandler_', 'Typoheads\\Formhandler\\', $className);
    if (false !== strstr($className, '_') && (false !== strstr($className, 'Typoheads\\Formhandler\\') || 1 === substr_count($className, '_'))) {
      $className = str_replace('_', '\\', $className);
    }
    if (1 === substr_count($className, '\\') && '\\Typoheads\\' !== substr($className, 0, 11)) {
      $className = 'Typoheads\\Formhandler\\'.$className;
    }
    if ('Typoheads\\Formhandler\\Validator\\Default' === $className) {
      $className = 'Typoheads\\Formhandler\\Validator\\DefaultValidator';
    }

    // @phpstan-ignore-next-line
    return ltrim($className, '\\');
  }

  /**
   * Read language file set in flexform or TypoScript, read the file's path to $this->langFile.
   *
   * @param string[]             $langFiles The formhandler lang files
   * @param array<string, mixed> &$settings The formhandler settings
   *
   * @return string[]
   */
  public static function readLanguageFiles(array $langFiles, array &$settings): array {
    // language file was not set in flexform, search TypoScript for setting
    if (empty($langFiles)) {
      $langFiles = [];
      if (isset($settings['langFile']) && !isset($settings['langFile.'])) {
        array_push($langFiles, self::resolveRelPathFromSiteRoot(strval($settings['langFile'])));
      } elseif (isset($settings['langFile'], $settings['langFile.'])) {
        array_push($langFiles, self::getSingle($settings, 'langFile'));
      } elseif (isset($settings['langFile.']) && is_array($settings['langFile.'])) {
        foreach ($settings['langFile.'] as $key => $langFile) {
          if (false === strpos((string) $key, '.')) {
            if (is_array($settings['langFile.'][$key.'.'])) {
              array_push($langFiles, self::getSingle($settings['langFile.'], $key));
            } else {
              array_push($langFiles, self::resolveRelPathFromSiteRoot($langFile));
            }
          }
        }
      }
    }
    foreach ($langFiles as &$langFile) {
      $langFile = self::convertToRelativePath($langFile);
    }

    return $langFiles;
  }

  /**
   * Read template file set in flexform or TypoScript, read the file's contents to $this->templateFile.
   *
   * @param string               $templateFile The template file name
   * @param array<string, mixed> &$settings    The formhandler settings
   */
  public static function readTemplateFile(string $templateFile, array &$settings): string {
    $templateCode = '';

    // template file was not set in flexform, search TypoScript for setting
    if (empty($templateFile)) {
      if (!isset($settings['templateFile']) && !isset($settings['templateFile.'])) {
        return '';
      }
      $templateFile = strval($settings['templateFile'] ?? '');

      if (isset($settings['templateFile.']) && is_array($settings['templateFile.'])) {
        foreach ($settings['templateFile.'] as $key => $template) {
          if (isset($settings['templateFile.'][$key.'.']) && is_array($settings['templateFile.'][$key.'.'])) {
            $templateFile = self::getSingle($settings['templateFile.'][$key.'.'], 'value');
          } else {
            $templateFile = self::getSingle($settings['templateFile.'][$key], 'value');
          }

          if (self::isTemplateFilePath($templateFile)) {
            $templateFile = self::resolvePath($templateFile);
            if (!@file_exists($templateFile)) {
              self::throwException('template_file_not_found', $templateFile);
            }
            $templateCode .= (\TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile) ?: '')."\n\n";
          } else {
            // The setting "templateFile" was a cObject which returned HTML content. Just use that as template code.
            $templateCode .= $templateFile."\n\n";
          }
        }
      } else {
        $templateFile = self::resolvePath($templateFile);
        if (!@file_exists($templateFile)) {
          self::throwException('template_file_not_found', $templateFile);
        }
        $templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile) ?: '';
      }
    } else {
      if (self::isTemplateFilePath($templateFile)) {
        $templateFile = self::resolvePath($templateFile);
        if (!@file_exists($templateFile)) {
          self::throwException('template_file_not_found', $templateFile);
        }
        $templateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($templateFile) ?: '';
      } else {
        // given variable $templateFile already contains the template code
        $templateCode = $templateFile;
      }
    }
    if (0 === strlen($templateCode)) {
      self::throwException('empty_template_file', $templateFile);
    }
    if (false === stristr($templateCode, '###TEMPLATE_')) {
      self::throwException('invalid_template_file', $templateFile);
    }

    return $templateCode;
  }

  /**
   * @param array<int|string, mixed>|string $values
   *
   * @return array<int|string, mixed>|string
   */
  public static function recursiveHtmlSpecialChars(array|string $values): array|string {
    if (is_array($values)) {
      if (empty($values)) {
        $values = '';
      } else {
        foreach ($values as &$value) {
          if (is_array($value)) {
            $value = self::recursiveHtmlSpecialChars($value);
          } else {
            if ($value instanceof Address) {
              $value = $value->toString();
            } else {
              $value = serialize($value);
            }
            $value = htmlspecialchars($value);
          }
        }
      }
    } else {
      $values = htmlspecialchars($values);
    }

    return $values;
  }

  /**
   * Removes unfilled markers from given template code.
   *
   * @param string $content The template code
   *
   * @return string The template code without markers
   */
  public static function removeUnfilledMarkers(string $content): string {
    return preg_replace('/###.*?###/', '', $content) ?? '';
  }

  /**
   * Substitutes EXT: with extension path in a file path.
   *
   * @param string $path The path
   *
   * @return string The resolved path
   */
  public static function resolvePath(string $path): string {
    if (MathUtility::canBeInterpretedAsInteger($path)) {
      /** @var ResourceFactory $resourceFactory */
      $resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ResourceFactory::class);

      $file = $resourceFactory->getFileObject(intval($path));
      $path = $file->getForLocalProcessing(false);
    } else {
      $path = explode('/', $path);
      if (0 === strpos($path[0], 'EXT')) {
        $parts = explode(':', $path[0]);
        $path[0] = ExtensionManagementUtility::extPath($parts[1]);
      }
      if (0 === strpos($path[0], 'typo3conf')) {
        unset($path[0], $path[1]);

        $path[2] = ExtensionManagementUtility::extPath($path[2]);
      }
      if (0 === strpos($path[1], 'typo3conf')) {
        unset($path[0], $path[1], $path[2]);

        $path[3] = ExtensionManagementUtility::extPath($path[3]);
      }
      $path = implode('/', $path);
      $path = str_replace('//', '/', $path);
    }

    return $path;
  }

  /**
   * Substitutes EXT: with extension path in a file path and returns the relative path.
   *
   * @param string $path The path
   *
   * @return string The resolved path
   */
  public static function resolveRelPath(string $path): string {
    $path = explode('/', $path);
    if (0 === strpos($path[0], 'EXT')) {
      $parts = explode(':', $path[0]);
      $path[0] = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath($parts[1]));
    }
    $path = implode('/', $path);

    return str_replace('//', '/', $path);
  }

  /**
   * Substitutes EXT: with extension path in a file path and returns the relative path from site root.
   *
   * @param string $path The path
   *
   * @return string The resolved path
   */
  public static function resolveRelPathFromSiteRoot(string $path): string {
    if ('http://' === substr($path, 0, 7)) {
      return $path;
    }
    $path = explode('/', $path);
    if (0 === strpos($path[0], 'EXT')) {
      return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(implode('/', $path));
    }
    $path = implode('/', $path);
    $path = str_replace('//', '/', $path);

    return str_replace('../', '', $path);
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
   * @param string $path The path
   *
   * @return string Sanitized path
   */
  public static function sanitizePath(string $path): string {
    if ('/' !== substr($path, 0, 1) && ':/' !== substr($path, 1, 2)) {
      $path = '/'.$path;
    }
    if ('/' !== substr($path, strlen($path) - 1) && !strstr($path, '.')) {
      $path = $path.'/';
    }
    while (strstr($path, '//')) {
      $path = str_replace('//', '/', $path);
    }

    return $path;
  }

  /**
   * copied from class tslib_content.
   *
   * Substitutes markers in given template string with data of marker array
   *
   * @param array<string, string> $markContentArray
   */
  public static function substituteMarkerArray(string $content, array $markContentArray): string {
    reset($markContentArray);
    foreach ($markContentArray as $marker => $markContent) {
      $content = str_replace($marker, $markContent, $content);
    }

    return $content;
  }

  /**
   * Manages the exception throwing.
   *
   * @param string $key Key in language file
   */
  public static function throwException(string $key): void {
    $message = self::getExceptionMessage($key);
    if (0 == strlen($message)) {
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
   * @param array<string, mixed> $settingsArray
   */
  public static function wrap(string $str, array $settingsArray, string $key): string {
    $wrappedString = $str;
    Globals::getCObj()?->setCurrentVal($wrappedString);
    if (isset($settingsArray[$key.'.']) && is_array($settingsArray[$key.'.'])) {
      $wrappedString = Globals::getCObj()?->stdWrap($str, $settingsArray[$key.'.']) ?? '';
    } elseif (isset($settingsArray[$key]) && strlen(strval($settingsArray[$key])) > 0) {
      $wrappedString = Globals::getCObj()?->wrap($str, strval($settingsArray[$key])) ?? '';
    }

    return $wrappedString;
  }
}
