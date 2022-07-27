<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\View;

use SJBR\SrFreecap\PiBaseApi;
use ThinkopenAt\Captcha\Utility;
use tx_jmrecaptcha;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
 * A default view for Formhandler.
 */
class Form extends AbstractView {
  /**
   * An array of fields to do not encode for output.
   *
   * @var string[]
   */
  protected array $disableEncodingFields = [];

  /**
   * An array of errors.
   *
   * @var array<string, mixed>
   */
  protected array $errors = [];

  /**
   * The template code.
   *
   * @var string[]
   */
  protected array $masterTemplates = [];

  /**
   * Fills the file specific markers:.
   *
   *  ###[fieldname]_minSize###
   *  ###[fieldname]_maxSize###
   *  ###[fieldname]_allowedTypes###
   *  ###[fieldname]_maxCount###
   *  ###[fieldname]_fileCount###
   *  ###[fieldname]_remainingCount###
   *
   *  ###[fieldname]_uploadedFiles###
   *  ###total_uploadedFiles###
   *
   * @param array<string, mixed> &$markers Reference to the markers array
   */
  public function fillFileMarkers(array &$markers): void {
    $settings = $this->parseSettings();

    $flexformValue = $this->utilityFuncs->pi_getFFvalue($this->cObj?->data['pi_flexform'] ?? [], 'required_fields', 'sMISC');
    if ($flexformValue) {
      $index = '1.';
      $fields = GeneralUtility::trimExplode(',', $flexformValue);
      if (is_array($settings['validators.'] ?? null)) {
        // Searches the index of Tx_Formhandler_Validator_Default
        foreach ((array) $settings['validators.'] as $index => $validator) {
          $currentValidatorClass = $this->utilityFuncs->getPreparedClassName((array) $validator);
          if ('Typoheads\\Formhandler\\Validator\\DefaultValidator' === $currentValidatorClass) {
            break;
          }
        }
      }
      $index = strval($index);

      $fieldConf = [];
      if (
        is_array($settings['validators.']) && isset($settings['validators.'][$index])
        && is_array($settings['validators.'][$index]) && isset($settings['validators.'][$index]['config.'])
        && is_array($settings['validators.'][$index]['config.']) && isset($settings['validators.'][$index]['config.']['fieldConf.'])
        && is_array($settings['validators.'][$index]['config.']['fieldConf.'])
      ) {
        $fieldConf = $settings['validators.'][$index]['config.']['fieldConf.'];
      }

      // Adds the value.
      foreach ($fields as $idx => $field) {
        $fieldConf[$field.'.']['errorCheck.'] = [];
        $fieldConf[$field.'.']['errorCheck.']['1'] = 'required';
      }

      if (
        is_array($settings['validators.']) && isset($settings['validators.'][$index])
        && is_array($settings['validators.'][$index]) && isset($settings['validators.'][$index]['config.'])
        && is_array($settings['validators.'][$index]['config.']) && isset($settings['validators.'][$index]['config.']['fieldConf.'])
        && is_array($settings['validators.'][$index]['config.']['fieldConf.'])
      ) {
        $settings['validators.'][$index]['config.']['fieldConf.'] = $fieldConf;
      }
    }

    $sessionFiles = (array) ($this->globals->getSession()?->get('files') ?? []);

    $requiredSign = $this->utilityFuncs->getSingle($settings, 'requiredSign');
    if (0 === strlen($requiredSign)) {
      $requiredSign = '*';
    }
    $requiredMarker = $this->utilityFuncs->getSingle($settings, 'requiredMarker');

    // parse validation settings
    if (isset($settings['validators.']) && is_array($settings['validators.'])) {
      if (0 === (int) $this->utilityFuncs->getSingle($settings['validators.'], 'disable')) {
        foreach ($settings['validators.'] as $key => $validatorSettings) {
          if (0 === (int) $this->utilityFuncs->getSingle($validatorSettings, 'disable')) {
            $disableErrorCheckFields = [];
            if (is_array($validatorSettings['config.']) && isset($validatorSettings['config.']['disableErrorCheckFields'])) {
              $disableErrorCheckFields = GeneralUtility::trimExplode(',', $validatorSettings['config.']['disableErrorCheckFields']);
            }
            if (is_array($validatorSettings['config.']) && is_array($validatorSettings['config.']['fieldConf.'])) {
              foreach ($validatorSettings['config.']['fieldConf.'] as $fieldname => $fieldSettings) {
                $replacedFieldname = str_replace('.', '', $fieldname);
                if (is_array($fieldSettings['errorCheck.'] ?? null)) {
                  foreach ($fieldSettings['errorCheck.'] as $key => $check) {
                    switch ($check) {
                      case 'fileMinSize':
                        $minSize = $fieldSettings['errorCheck.'][$key.'.']['minSize'];
                        $markers['###'.$replacedFieldname.'_minSize###'] = GeneralUtility::formatSize($minSize, ' Bytes| KB| MB| GB');

                        break;

                      case 'fileMaxSize':
                        $maxSize = $fieldSettings['errorCheck.'][$key.'.']['maxSize'];
                        $markers['###'.$replacedFieldname.'_maxSize###'] = GeneralUtility::formatSize($maxSize, ' Bytes| KB| MB| GB');

                        break;

                      case 'fileAllowedTypes':
                        $types = $fieldSettings['errorCheck.'][$key.'.']['allowedTypes'];
                        $markers['###'.$replacedFieldname.'_allowedTypes###'] = $types;

                        break;

                      case 'fileMaxCount':
                        $maxCount = $fieldSettings['errorCheck.'][$key.'.']['maxCount'];
                        $markers['###'.$replacedFieldname.'_maxCount###'] = $maxCount;

                        if (is_array($sessionFiles[$replacedFieldname])) {
                          $fileCount = count($sessionFiles[$replacedFieldname]);
                        } else {
                          $fileCount = 0;
                        }
                        $markers['###'.$replacedFieldname.'_fileCount###'] = $fileCount;

                        $remaining = $maxCount - $fileCount;
                        $markers['###'.$replacedFieldname.'_remainingCount###'] = $remaining;

                        break;

                      case 'fileMinCount':
                        $minCount = $fieldSettings['errorCheck.'][$key.'.']['minCount'];
                        $markers['###'.$replacedFieldname.'_minCount###'] = $minCount;

                        break;

                      case 'fileMaxTotalSize':
                        $maxTotalSize = $fieldSettings['errorCheck.'][$key.'.']['maxTotalSize'];
                        $markers['###'.$replacedFieldname.'_maxTotalSize###'] = GeneralUtility::formatSize($maxTotalSize, ' Bytes| KB| MB| GB');
                        $totalSize = 0;
                        if (is_array($sessionFiles[$replacedFieldname])) {
                          foreach ($sessionFiles[$replacedFieldname] as $file) {
                            $totalSize += (int) $file['size'];
                          }
                        }
                        $markers['###'.$replacedFieldname.'_currentTotalSize###'] = GeneralUtility::formatSize($totalSize, ' Bytes| KB| MB| GB');
                        $markers['###'.$replacedFieldname.'_remainingTotalSize###'] = GeneralUtility::formatSize($maxTotalSize - $totalSize, ' Bytes| KB| MB| GB');

                        break;

                      case 'required':
                      case 'fileRequired':
                      case 'jmRecaptcha':
                      case 'captcha':
                      case 'srFreecap':
                      case 'mathGuard':
                        if (!in_array('all', $disableErrorCheckFields) && !in_array($replacedFieldname, $disableErrorCheckFields)) {
                          $markers['###required_'.$replacedFieldname.'###'] = $requiredSign;
                          $markers['###requiredMarker_'.$replacedFieldname.'###'] = $requiredMarker;
                        }

                        break;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    if (is_array($sessionFiles)) {
      $singleFileMarkerTemplate = (array) ($settings['singleFileMarkerTemplate.'] ?? []);
      $totalFilesMarkerTemplate = (array) ($settings['totalFilesMarkerTemplate.'] ?? []);

      foreach ($sessionFiles as $field => $files) {
        foreach ($files as $idx => $fileInfo) {
          $filename = $fileInfo['name'];
          $thumb = '';
          if (1 === intval($singleFileMarkerTemplate['showThumbnails'] ?? 0) || 2 === intval($singleFileMarkerTemplate['showThumbnails'] ?? 0)) {
            $imgConf['image.'] = $singleFileMarkerTemplate['image.'];
            $thumb = $this->getThumbnail($imgConf, $fileInfo);
          }
          $text = $this->utilityFuncs->getSingle((array) ($settings['files.'] ?? []), 'customRemovalText');
          if (0 === strlen($text)) {
            $text = 'X';
          }
          $link = '';
          $uploadedFileName = $fileInfo['uploaded_name'];
          if (!$uploadedFileName) {
            $uploadedFileName = $fileInfo['name'];
          }
          if (null != $this->globals->getAjaxHandler() && 1 == intval(((array) ($settings['files.'] ?? []))['enableAjaxFileRemoval'] ?? 0)) {
            $link = $this->globals->getAjaxHandler()->getFileRemovalLink($text, $field, $uploadedFileName);
          } elseif (1 == intval(((array) ($settings['files.'] ?? []))['enableFileRemoval'] ?? 0)) {
            $submitName = 'step-'.strval($this->globals->getSession()?->get('currentStep') ?? '1').'-reload';
            if ($this->globals->getFormValuesPrefix()) {
              $submitName = $this->globals->getFormValuesPrefix().'['.$submitName.']';
            }
            $onClick = "
							document.getElementById('removeFile-".$this->globals->getRandomID()."').value='".$uploadedFileName."';
							document.getElementById('removeFileField-".$this->globals->getRandomID()."').value='".$field."';
							document.getElementById('submitField-".$this->globals->getRandomID()."').name='".$submitName."';
							document.getElementById('ieHiddenField-".$this->globals->getRandomID()."').name='dummy';
						";

            if ($this->globals->getFormID()) {
              $onClick .= "document.getElementById('".$this->globals->getFormID()."').submit();";
            } else {
              $onClick .= 'document.forms[0].submit();';
            }

            $onClick .= 'return false;';

            $link = '<a href="javascript:void(0)" class="formhandler_removelink" onclick="'.str_replace(["\n", '	'], '', $onClick).'" >'.$text.'</a>';
          }
          $stdWrappedFilename = $this->utilityFuncs->wrap($filename, (array) ($this->settings['singleFileMarkerTemplate.'] ?? []), 'filenameWrap');

          $wrappedFilename = $this->utilityFuncs->wrap($stdWrappedFilename.$link, $singleFileMarkerTemplate, 'singleWrap');
          $wrappedThumb = $this->utilityFuncs->wrap($thumb.$link, $singleFileMarkerTemplate, 'singleWrap');
          $wrappedThumbFilename = $this->utilityFuncs->wrap($thumb.' '.$stdWrappedFilename.$link, $singleFileMarkerTemplate, 'singleWrap');
          if (1 === intval($singleFileMarkerTemplate['showThumbnails'] ?? 0)) {
            $markers['###'.$field.'_uploadedFiles###'] .= $wrappedThumb;
          } elseif (2 === intval($singleFileMarkerTemplate['showThumbnails'] ?? 0)) {
            $markers['###'.$field.'_uploadedFiles###'] .= $wrappedThumbFilename;
          } else {
            $markers['###'.$field.'_uploadedFiles###'] .= $wrappedFilename;
          }
          if (1 === intval($totalFilesMarkerTemplate['showThumbnails'] ?? 0) || 2 === intval($totalFilesMarkerTemplate['showThumbnails'] ?? 0)) {
            $imgConf['image.'] = (array) ($totalFilesMarkerTemplate['image.'] ?? []);
            if (empty($imgConf['image.'])) {
              $imgConf['image.'] = (array) ($singleFileMarkerTemplate['image.'] ?? []);
            }
            $thumb = $this->getThumbnail($imgConf, $fileInfo);
          }
          $stdWrappedFilename = $this->utilityFuncs->wrap($filename, (array) ($this->settings['totalFilesMarkerTemplate.'] ?? []), 'filenameWrap');

          $wrappedFilename = $this->utilityFuncs->wrap($stdWrappedFilename.$link, $totalFilesMarkerTemplate, 'singleWrap');
          $wrappedThumb = $this->utilityFuncs->wrap($thumb.$link, $totalFilesMarkerTemplate, 'singleWrap');
          $wrappedThumbFilename = $this->utilityFuncs->wrap($thumb.' '.$stdWrappedFilename.$link, $totalFilesMarkerTemplate, 'singleWrap');

          if (1 === intval($totalFilesMarkerTemplate['showThumbnails'] ?? 0)) {
            $markers['###total_uploadedFiles###'] .= $wrappedThumb;
          } elseif (2 === intval($totalFilesMarkerTemplate['showThumbnails'] ?? 0)) {
            $markers['###total_uploadedFiles###'] .= $wrappedThumbFilename;
          } else {
            $markers['###total_uploadedFiles###'] .= $wrappedFilename;
          }
        }
        $markers['###'.$field.'_uploadedFiles###'] = $this->utilityFuncs->wrap(strval($markers['###'.$field.'_uploadedFiles###'] ?? ''), $singleFileMarkerTemplate, 'totalWrap');
        $markers['###'.$field.'_uploadedFiles###'] = '<div id="Tx_Formhandler_UploadedFiles_'.$field.'">'.$markers['###'.$field.'_uploadedFiles###'].'</div>';
      }
      $markers['###total_uploadedFiles###'] = $this->utilityFuncs->wrap(strval($markers['###total_uploadedFiles###'] ?? ''), $totalFilesMarkerTemplate, 'totalWrap');
      $markers['###TOTAL_UPLOADEDFILES###'] = $markers['###total_uploadedFiles###'];
      $markers['###total_uploadedfiles###'] = $markers['###total_uploadedFiles###'];
    }

    $requiredSign = $this->utilityFuncs->getSingle($settings, 'requiredSign');
    if (0 === strlen($requiredSign)) {
      $requiredSign = '*';
    }
    $markers['###required###'] = $requiredSign;
    $markers['###REQUIRED###'] = $markers['###required###'];
  }

  /**
   * Main method called by the controller.
   *
   * @param array<string, mixed> $gp     The current GET/POST parameters
   * @param array<string, mixed> $errors The errors occurred in validation
   *
   * @return string content
   */
  public function render(array $gp, array $errors): string {
    // set GET/POST parameters
    $this->gp = $gp;

    // set template
    $this->template = trim(strval($this->subparts['template'] ?? ''));
    if (empty($this->template)) {
      $this->template = $this->globals->getTemplateCode();
    }
    if (empty($this->template)) {
      $this->utilityFuncs->throwException('no_template_file');
    }

    $this->errors = $errors;

    // set language file
    if (!$this->langFiles) {
      $this->langFiles = $this->globals->getLangFiles();
    }

    // fill Typoscript markers
    if (isset($this->settings['markers.']) && is_array($this->settings['markers.'])) {
      $this->fillTypoScriptMarkers();
    }

    // read master template
    if (!$this->masterTemplates) {
      $this->readMasterTemplates();
    }

    if (!empty($this->masterTemplates)) {
      $count = 0;
      while ($count < 5 && preg_match('/###(field|master)_[^#]*###/', $this->template)) {
        $this->replaceMarkersFromMaster();
        ++$count;
      }
    }

    if (null != $this->globals->getAjaxHandler()) {
      $markers = [];
      $this->globals->getAjaxHandler()->fillAjaxMarkers($markers);
      $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    // fill Typoscript markers
    if (isset($this->settings['markers.']) && is_array($this->settings['markers.'])) {
      $this->fillTypoScriptMarkers();
    }

    $this->substituteConditionalSubparts('has_translation');
    if (!isset($this->gp['submitted']) || !$this->gp['submitted']) {
      $this->storeStartEndBlock();
    } elseif (1 !== intval($this->globals->getSession()?->get('currentStep') ?? 1)) {
      $this->fillStartEndBlock();
    }

    if (1 === intval($this->settings['fillValueMarkersBeforeLangMarkers'] ?? 0)) {
      // fill value_[fieldname] markers
      $this->fillValueMarkers();
    }

    // fill LLL:[language_key] markers
    $this->fillLangMarkers();

    // substitute ISSET markers
    $this->substituteConditionalSubparts('isset');

    // substitute IF markers
    $this->substituteConditionalSubparts('if');

    // fill default markers
    $this->fillDefaultMarkers();

    if (1 !== intval($this->settings['fillValueMarkersBeforeLangMarkers'] ?? 0)) {
      // fill value_[fieldname] markers
      $this->fillValueMarkers();
    }

    // fill selected_[fieldname]_value markers and checked_[fieldname]_value markers
    $this->fillSelectedMarkers();

    // fill error_[fieldname] markers
    if (!empty($errors)) {
      $this->fillIsErrorMarkers($errors);
      $this->fillErrorMarkers($errors);
    }

    $this->fillIsSuccessMarkers($errors);

    // fill LLL:[language_key] markers again to make language markers in other markers possible
    $this->fillLangMarkers();

    // fill Typoscript markers
    if (isset($this->settings['markers.']) && is_array($this->settings['markers.'])) {
      $this->fillTypoScriptMarkers();
    }

    // remove markers that were not substituted
    $content = $this->utilityFuncs->removeUnfilledMarkers($this->template);

    if (isset($this->settings['stdWrap.']) && is_array($this->settings['stdWrap.'])) {
      $content = $this->cObj?->stdWrap($content, $this->settings['stdWrap.']) ?? '';
    }
    if (1 !== intval($this->settings['disableWrapInBaseClass'] ?? 0)) {
      if (!isset($this->frontendController->config['config']['disablePrefixComment'])) {
        $this->frontendController->config['config']['disablePrefixComment'] = true;
      }

      $content = $this->pi_wrapInBaseClass($content);
    }

    return $content;
  }

  /**
   * improved copy from dam_index.
   *
   * Returns HTML of a box with a step counter and "back" and "next" buttons
   * Use label "next"/"prev" or "next_[stepnumber]"/"prev_[stepnumber]" for specific step in language file as button text.
   *
   * <code>
   * #set background color
   * plugin.tx_formhandler_pi1.settings.stepbar_color = #EAEAEA
   * #use default CSS, written to temp file
   * plugin.tx_formhandler_pi1.settings.useDefaultStepBarStyles = 1
   * </code>
   *
   * @author Johannes Feustel
   *
   * @param int    $currentStep    current step (begins with 1)
   * @param int    $lastStep       last step
   * @param string $buttonNameBack name attribute of the back button
   * @param string $buttonNameFwd  name attribute of the forward button
   *
   * @return string HTML code
   */
  protected function createStepBar(int $currentStep, int $lastStep, string $buttonNameBack = '', string $buttonNameFwd = ''): string {
    // colors
    $bgcolor = '#EAEAEA';
    $bgcolor = isset($this->settings['stepbar_color']) ? strval($this->settings['stepbar_color']) : $bgcolor;

    $nrcolor = $this->utilityFuncs->modifyHTMLcolor($bgcolor, 30, 30, 30);
    $errorbgcolor = '#dd7777';
    $errornrcolor = $this->utilityFuncs->modifyHTMLcolor($errorbgcolor, 30, 30, 30);

    $classprefix = $this->globals->getFormValuesPrefix().'_stepbar';

    $css = [];
    $css[] = '.'.$classprefix.' { background:'.$bgcolor.'; padding:4px;}';
    $css[] = '.'.$classprefix.'_error { background: '.$errorbgcolor.';}';
    $css[] = '.'.$classprefix.'_steps { margin-left:50px; margin-right:25px; vertical-align:middle; font-family:Verdana,Arial,Helvetica; font-size:22px; font-weight:bold; }';
    $css[] = '.'.$classprefix.'_steps span { color:'.$nrcolor.'; margin-left:5px; margin-right:5px; }';
    $css[] = '.'.$classprefix.'_error .'.$classprefix.'_steps span { color:'.$errornrcolor.'; margin-left:5px; margin-right:5px; }';
    $css[] = '.'.$classprefix.'_steps .'.$classprefix.'_currentstep { color:  #000;}';
    $css[] = '#stepsFormButtons { margin-left:25px;vertical-align:middle;}';

    $content = '';
    $buttons = '';

    for ($i = 1; $i <= $lastStep; ++$i) {
      $class = '';
      if ($i == $currentStep) {
        $class = 'class="'.$classprefix.'_currentstep"';
      }
      $stepName = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'step-'.$i);
      if (0 === strlen($stepName)) {
        $stepName = $i;
      }
      $content .= '<span '.$class.' >'.$stepName.'</span>';
    }
    $content = '<span class="'.$classprefix.'_steps'.'">'.$content.'</span>';

    // if not the first step, show back button
    if ($currentStep > 1) {
      // check if label for specific step
      $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'prev_'.$currentStep);
      if (0 === strlen($message)) {
        $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'prev');
      }
      $buttons .= '<input type="submit" name="'.$buttonNameBack.'" value="'.trim($message).'" class="button_prev" style="margin-right:10px;" />';
    }
    $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'next_'.$currentStep);
    if (0 === strlen($message)) {
      $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'next');
    }
    $buttons .= '<input type="submit" name="'.$buttonNameFwd.'" value="'.trim($message).'" class="button_next" />';

    $content .= '<span id="stepsFormButtons">'.$buttons.'</span>';

    // wrap
    $classes = $classprefix;
    if ($this->errors) {
      $classes = $classes.' '.$classprefix.'_error';
    }
    $content = '<div class="'.$classes.'" >'.$content.'</div>';

    // add default css to page
    if (isset($this->settings['useDefaultStepBarStyles']) && (bool) $this->settings['useDefaultStepBarStyles']) {
      $css = implode("\n", $css);
      $css = self::inline2TempFile($css, 'css');

      /** @var Typo3Version $typo3Version */
      $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
      if (version_compare($typo3Version->getVersion(), '4.3.0') >= 0) {
        $css = '<link rel="stylesheet" type="text/css" href="'.htmlspecialchars($css).'" />';
      }
      $GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'_'.$classprefix] .= $css;
    }

    return $content;
  }

  /**
   * Fills the markers for the supported captcha extensions.
   *
   * @param array<string, mixed> &$markers Reference to the markers array
   */
  protected function fillCaptchaMarkers(array &$markers): void {
    if (stristr($this->template, '###CAPTCHA###') && ExtensionManagementUtility::isLoaded('captcha')) {
      $markers['###CAPTCHA###'] = Utility::makeCaptcha();
      $markers['###captcha###'] = $markers['###CAPTCHA###'];
    }
    if (stristr($this->template, '###SR_FREECAP_IMAGE###') && ExtensionManagementUtility::isLoaded('sr_freecap')) {
      require_once ExtensionManagementUtility::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php';

      /** @var PiBaseApi $freeCap */
      $freeCap = GeneralUtility::makeInstance(PiBaseApi::class);
      $markers = array_merge($markers, $freeCap->makeCaptcha());
    }
    if (stristr($this->template, '###RECAPTCHA###') && ExtensionManagementUtility::isLoaded('jm_recaptcha')) {
      require_once ExtensionManagementUtility::extPath('jm_recaptcha').'class.tx_jmrecaptcha.php';

      $recaptcha = new tx_jmrecaptcha();
      $markers['###RECAPTCHA###'] = $recaptcha->getReCaptcha();
      $markers['###recaptcha###'] = $markers['###RECAPTCHA###'];
    }
  }

  /**
   * Substitutes default markers in $this->template.
   */
  protected function fillDefaultMarkers(): void {
    $parameters = (array) GeneralUtility::_GET();
    if (isset($parameters['id'])) {
      unset($parameters['id']);
    }
    if (isset($parameters['eID'])) {
      unset($parameters['eID']);
    }
    if (isset($parameters['randomID'])) {
      unset($parameters['randomID']);
    }

    try {
      $path = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', $parameters);
    } catch (\OutOfRangeException $e) {
      $path = $this->pi_getPageLink($GLOBALS['TSFE']->id);
    }

    $path = preg_replace('/ADMCMD_[^=]+=[^&]+(&)?/', '', $path) ?? '';
    $path = htmlspecialchars($path);

    $randomID = htmlspecialchars(strval($this->gp['randomID'] ?? ''));
    $markers = [];
    $markers['###REL_URL###'] = $path;
    $markers['###TIMESTAMP###'] = time();

    // Calculate timestamp only once to prevent false positives when a small error in the form gets corrected fast.
    $formtime = trim(strval($this->gp['formtime'] ?? ''));
    if (!empty($formtime)) {
      $markers['###TIMESTAMP###'] = htmlspecialchars($formtime);
    }
    $markers['###RANDOM_ID###'] = $randomID;
    $markers['###ABS_URL###'] = GeneralUtility::locationHeaderUrl($path);
    $markers['###rel_url###'] = $markers['###REL_URL###'];
    $markers['###timestamp###'] = $markers['###TIMESTAMP###'];
    $markers['###abs_url###'] = $markers['###ABS_URL###'];

    $markers['###formID###'] = htmlspecialchars($this->globals->getFormID());

    $name = 'submitted';
    if ($this->globals->getFormValuesPrefix()) {
      $name = $this->globals->getFormValuesPrefix().'[submitted]';
    }
    $markers['###HIDDEN_FIELDS###'] = '
			<input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />
			<input type="hidden" name="'.htmlspecialchars($name).'" value="1" />
		';

    $name = 'randomID';
    if ($this->globals->getFormValuesPrefix()) {
      $name = $this->globals->getFormValuesPrefix().'[randomID]';
    }
    $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" name="'.htmlspecialchars($name).'" value="'.$randomID.'" />
		';

    $name = 'removeFile';
    if ($this->globals->getFormValuesPrefix()) {
      $name = $this->globals->getFormValuesPrefix().'[removeFile]';
    }
    $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" id="removeFile-'.$randomID.'" name="'.htmlspecialchars($name).'" value="" />
		';

    $name = 'removeFileField';
    if ($this->globals->getFormValuesPrefix()) {
      $name = $this->globals->getFormValuesPrefix().'[removeFileField]';
    }
    $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" id="removeFileField-'.$randomID.'" name="'.htmlspecialchars($name).'" value="" />
		';

    $name = 'submitField';
    if ($this->globals->getFormValuesPrefix()) {
      $name = $this->globals->getFormValuesPrefix().'[submitField]';
    }
    $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" id="submitField-'.$randomID.'" name="'.htmlspecialchars($name).'" value="" />
		';

    $name = 'formToken';
    if ($this->globals->getFormValuesPrefix()) {
      $name = $this->globals->getFormValuesPrefix().'[formToken]';
    }
    if (isset($this->gp['formToken'])) {
      $markers['###HIDDEN_FIELDS###'] .= '
				<input type="hidden" name="'.$name.'" value="'.$randomID.'" />
			';
    }

    if (isset($this->settings['session.']) && is_array($this->settings['session.'])
            && 1 === (int) $this->utilityFuncs->getSingle($this->settings['session.']['config.'], 'disableCookies')
            && 0 === (int) ini_get('session.use_trans_sid')
    ) {
      /*
       * User currently does not have a session cookie and php is not configured to
       * automatically add session info to forms and links
       */
      $markers['###HIDDEN_FIELDS###'] .= '
				<input type="hidden" name="'.session_name().'" value="'.session_id().'" />
			';
    }
    $currentStepFromSession = intval($this->globals->getSession()?->get('currentStep') ?? 1);
    $lastStepFromSession = intval($this->globals->getSession()?->get('lastStep') ?? 1);
    $totalStepsFromSession = intval($this->globals->getSession()?->get('totalSteps') ?? 1);

    $previousStep = $currentStepFromSession - 1;
    $nextStep = $currentStepFromSession + 1;
    $hiddenActionFieldName = 'step-';
    $prefix = $this->globals->getFormValuesPrefix();
    if ($prefix) {
      $hiddenActionFieldName = $prefix.'['.$hiddenActionFieldName.'#step#-#action#]';
    } else {
      $hiddenActionFieldName = $hiddenActionFieldName.'#step#-#action#';
    }

    // submit name for next page
    $hiddenActionFieldName = ' name="'.str_replace('#action#', 'next', $hiddenActionFieldName).'" ';
    $hiddenActionFieldName = str_replace('#step#', (string) $nextStep, $hiddenActionFieldName);

    $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" '.$hiddenActionFieldName.' id="ieHiddenField-'.$randomID.'" value="1" />
		';

    $markers['###formValuesPrefix###'] = $this->globals->getFormValuesPrefix();

    if (isset($this->gp['generated_authCode'])) {
      $markers['###auth_code###'] = htmlspecialchars(strval($this->gp['generated_authCode']));
    }

    $markers['###ip###'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
    $markers['###IP###'] = $markers['###ip###'];
    $markers['###submission_date###'] = date('d.m.Y H:i:s', time());
    $markers['###pid###'] = $GLOBALS['TSFE']->id;
    $markers['###PID###'] = $markers['###pid###'];

    // current step
    $markers['###curStep###'] = $currentStepFromSession;

    // maximum step/number of steps
    $markers['###maxStep###'] = $totalStepsFromSession;

    // the last step shown
    $markers['###lastStep###'] = $lastStepFromSession;

    $name = 'step-';
    $prefix = $this->globals->getFormValuesPrefix();
    if ($prefix) {
      $name = $prefix.'['.$name.'#step#-#action#]';
    } else {
      $name = $name.'#step#-#action#';
    }

    // submit name for next page
    $nextName = ' name="'.str_replace('#action#', 'next', $name).'" ';
    $nextName = str_replace('#step#', (string) $nextStep, $nextName);
    $markers['###submit_nextStep###'] = $nextName;

    // submit name for previous page
    $prevName = ' name="'.str_replace('#action#', 'prev', $name).'" ';
    $allowStepJumps = false;
    if (isset($this->settings['allowStepJumps'])) {
      $allowStepJumps = (bool) $this->utilityFuncs->getSingle($this->settings, 'allowStepJumps');
    }
    if ($allowStepJumps && $lastStepFromSession < $currentStepFromSession) {
      $previousStep = $lastStepFromSession;
    }
    if ($previousStep < 1) {
      $previousStep = 1;
    }
    $prevName = str_replace('#step#', (string) $previousStep, $prevName);
    $markers['###submit_prevStep###'] = $prevName;

    // submits for next/prev steps with template suffix
    preg_match_all('/###submit_nextStep_[^#]+?###/Ssm', $this->template, $allNextSubmits);
    foreach ($allNextSubmits[0] as $nextSubmitSuffix) {
      $nextSubmitSuffix = substr($nextSubmitSuffix, 19, -3);
      $nextName = ' name="'.str_replace('#action#', 'next', $name).'['.$nextSubmitSuffix.']" ';
      $nextName = str_replace('#step#', (string) $nextStep, $nextName);
      $markers['###submit_nextStep_'.$nextSubmitSuffix.'###'] = $nextName;
    }

    preg_match_all('/###submit_prevStep_[^#]+?###/Ssm', $this->template, $allPrevSubmits);
    foreach ($allPrevSubmits[0] as $prevSubmitSuffix) {
      $prevSubmitSuffix = substr($prevSubmitSuffix, 19, -3);
      $prevName = ' name="'.str_replace('#action#', 'prev', $name).'['.$prevSubmitSuffix.']" ';
      $prevName = str_replace('#step#', (string) $nextStep, $prevName);
      $markers['###submit_prevStep_'.$prevSubmitSuffix.'###'] = $prevName;
    }

    // submit name for reloading the same page/step
    $reloadName = ' name="'.str_replace('#action#', 'reload', $name).'" ';
    $reloadName = str_replace('#step#', (string) $currentStepFromSession, $reloadName);
    $markers['###submit_reload###'] = $reloadName;

    preg_match_all('/###submit_step_([^#])+?###/Ssm', $this->template, $allJumpToStepSubmits);
    foreach ($allJumpToStepSubmits[0] as $idx => $allJumpToStepSubmit) {
      $step = (int) $allJumpToStepSubmits[1][$idx];
      $action = 'next';
      if ($step < $currentStepFromSession) {
        $action = 'prev';
      }
      $submitName = ' name="'.str_replace('#action#', $action, $name).'" ';
      $submitName = str_replace('#step#', (string) $step, $submitName);
      $markers['###submit_step_'.$step.'###'] = $submitName;
    }

    // step bar
    $prevName = str_replace('#action#', 'prev', $name);
    $prevName = str_replace('#step#', (string) $previousStep, $prevName);
    $nextName = str_replace('#action#', 'next', $name);
    $nextName = str_replace('#step#', (string) $nextStep, $nextName);
    $markers['###step_bar###'] = $this->createStepBar(
      $currentStepFromSession,
      $totalStepsFromSession,
      $prevName,
      $nextName
    );

    $this->fillCaptchaMarkers($markers);
    $this->fillFEUserMarkers($markers);
    $this->fillFileMarkers($markers);

    if (!strstr($this->template, '###HIDDEN_FIELDS###')) {
      $this->template = preg_replace(
        '/(<form[^>]*>)/i',
        '$1<fieldset style="display: none;">'.$markers['###HIDDEN_FIELDS###'].'</fieldset>',
        $this->template
      ) ?? '';
    }

    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
  }

  /**
   * Substitutes markers
   *        ###error_[fieldname]###
   *        ###ERROR###
   * in $this->template.
   *
   * @param array<string, mixed> $errors
   */
  protected function fillErrorMarkers(array &$errors): void {
    $markers = [];
    $singleErrorTemplate = (array) ($this->settings['singleErrorTemplate.'] ?? []);
    $errorListTemplate = (array) ($this->settings['errorListTemplate.'] ?? []);
    foreach ($errors as $field => $types) {
      $errorMessages = [];
      $temp = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_'.$field);
      if (strlen($temp) > 0) {
        $errorMessage = $this->utilityFuncs->wrap($temp, $singleErrorTemplate, 'singleWrap');
        $errorMessages[] = $errorMessage;
      }
      if (!is_array($types)) {
        $types = [$types];
      }
      foreach ($types as $idx => $type) {
        if (is_array($type)) {
          $this->fillErrorMarkers($type);

          continue;
        }

        $temp = GeneralUtility::trimExplode(';', $type);
        $type = array_shift($temp) ?? '';
        foreach ($temp as $subIdx => $item) {
          $item = GeneralUtility::trimExplode('::', $item);
          $values[$item[0]] = $item[1];
        }

        // try to load specific error message with key like error_fieldname_integer
        $errorMessage = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_'.$field.'_'.$type);
        if (0 === strlen($errorMessage)) {
          $type = strtolower($type);
          $errorMessage = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_'.$field.'_'.$type);
        }
        // Still no error message found, try to find a less specific one
        if (0 === strlen($errorMessage)) {
          $type = strtolower($type);
          $errorMessage = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_default_'.$type);
        }
        if ($errorMessage) {
          $errorMessage = str_replace(['###fieldname###', '###FIELDNAME###'], $field, $errorMessage);
          if (isset($values) && is_array($values)) {
            foreach ($values as $key => $value) {
              $errorMessage = str_replace('###'.$key.'###', $value, $errorMessage);
            }
          }
          $errorMessage = $this->utilityFuncs->wrap($errorMessage, $singleErrorTemplate, 'singleWrap');
          $errorMessages[] = $errorMessage;
        } else {
          $this->utilityFuncs->debugMessage('no_error_message', ['error_'.$field.'_'.$type], 2);
        }
      }
      $errorMessage = implode('', $errorMessages);
      $errorMessage = $this->utilityFuncs->wrap($errorMessage, $singleErrorTemplate, 'totalWrap');
      $clearErrorMessage = $errorMessage;
      if ($this->settings['addErrorAnchors']) {
        $errorMessage = '<a name="'.$field.'-'.$this->globals->getRandomID().'">'.$errorMessage.'</a>';
      }
      $langMarkers = $this->utilityFuncs->getFilledLangMarkers($errorMessage, $this->langFiles);
      $errorMessage = $this->markerBasedTemplateService->substituteMarkerArray($errorMessage, $langMarkers);
      $markers['###error_'.$field.'###'] = $errorMessage;
      $markers['###ERROR_'.strtoupper($field).'###'] = $errorMessage;
      $errorMessage = $clearErrorMessage;
      if ($this->settings['addErrorAnchors']) {
        $baseUrl = strval(GeneralUtility::getIndpEnv('REQUEST_URI'));
        if ($this->globals->isAjaxMode()) {
          $baseUrl = strval(GeneralUtility::getIndpEnv('HTTP_REFERER'));
        }
        $errorMessage = '<a href="'.$baseUrl.'#'.$field.'-'.$this->globals->getRandomID().'">'.$errorMessage.'</a>';
      }

      // list settings
      $errorMessage = $this->utilityFuncs->wrap($errorMessage, $errorListTemplate, 'singleWrap');

      $markers['###ERROR###'] = ($markers['###ERROR###'] ?? '').$errorMessage;
    }
    $markers['###ERROR###'] = $this->utilityFuncs->wrap($markers['###ERROR###'], $errorListTemplate, 'totalWrap');
    $langMarkers = $this->utilityFuncs->getFilledLangMarkers($markers['###ERROR###'], $this->langFiles);
    $markers['###ERROR###'] = $this->markerBasedTemplateService->substituteMarkerArray($markers['###ERROR###'], $langMarkers);
    $markers['###error###'] = $markers['###ERROR###'];
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
  }

  /**
   * Fills the markers ###FEUSER_[property]### with the data from $GLOBALS["TSFE"]->fe_user->user.
   *
   * @param array<string, mixed> &$markers Reference to the markers array
   */
  protected function fillFEUserMarkers(array &$markers): void {
    if (is_array($GLOBALS['TSFE']->fe_user->user)) {
      foreach ($GLOBALS['TSFE']->fe_user->user as $k => $v) {
        $markers['###FEUSER_'.strtoupper($k).'###'] = $v;
        $markers['###FEUSER_'.strtolower($k).'###'] = $v;
        $markers['###feuser_'.strtoupper($k).'###'] = $v;
        $markers['###feuser_'.strtolower($k).'###'] = $v;
      }
    }
  }

  /**
   * Substitutes markers
   *        ###is_error_[fieldname]###
   *        ###is_error###
   * in $this->template.
   *
   * @param array<string, mixed> $errors
   */
  protected function fillIsErrorMarkers(array $errors): void {
    $markers = [];
    $errorMessage = '';
    foreach ($errors as $field => $types) {
      if (is_array($this->settings['isErrorMarker.']) && isset($this->settings['isErrorMarker.'][$field])) {
        $errorMessage = $this->utilityFuncs->getSingle($this->settings['isErrorMarker.'], $field);
      } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_error_'.$field))) > 0) {
        $errorMessage = $temp;
      } elseif (is_array($this->settings['isErrorMarker.']) && isset($this->settings['isErrorMarker.']['default'])) {
        $errorMessage = $this->utilityFuncs->getSingle($this->settings['isErrorMarker.'], 'default');
      } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_error_default'))) > 0) {
        $errorMessage = $temp;
      }
      $markers['###is_error_'.$field.'###'] = $errorMessage;
    }
    if (is_array($this->settings['isErrorMarker.']) && isset($this->settings['isErrorMarker.']['global'])) {
      $errorMessage = $this->utilityFuncs->getSingle($this->settings['isErrorMarker.'], 'global');
    } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_error'))) > 0) {
      $errorMessage = $temp;
    }
    $markers['###is_error###'] = $errorMessage;
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
  }

  /**
   * Substitutes markers
   *        ###is_success_[fieldname]###
   * in $this->template.
   *
   * @param array<string, mixed> $errors
   */
  protected function fillIsSuccessMarkers(array $errors): void {
    $markers = [];
    $successMessage = '';
    foreach ($this->gp as $field => $value) {
      if (!isset($errors[$field]) && isset($this->settings['isSuccessMarker.']) && is_array($this->settings['isSuccessMarker.'])) {
        if ($this->settings['isSuccessMarker.'][$field]) {
          $successMessage = $this->utilityFuncs->getSingle($this->settings['isSuccessMarker.'], $field);
        } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_success_'.$field))) > 0) {
          $successMessage = $temp;
        } elseif ($this->settings['isSuccessMarker.']['default']) {
          $successMessage = $this->utilityFuncs->getSingle($this->settings['isSuccessMarker.'], 'default');
        } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_success_default'))) > 0) {
          $successMessage = $temp;
        }
        $markers['###is_success_'.$field.'###'] = $successMessage;
      }
    }
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
  }

  /**
   * Substitutes markers
   *        ###LLL:[languageKey]###
   * in $this->template.
   */
  protected function fillLangMarkers(): void {
    $langMarkers = [];
    if (is_array($this->langFiles)) {
      $aLLMarkerList = [];
      preg_match_all('/###LLL:[^#]+?###/Ssm', $this->template, $aLLMarkerList);
      foreach ($aLLMarkerList[0] as $idx => $LLMarker) {
        $llKey = substr($LLMarker, 7, strlen($LLMarker) - 10);
        $marker = $llKey;
        $message = '';
        foreach ($this->langFiles as $subIdx => $langFile) {
          $temp = trim(LocalizationUtility::translate('LLL:'.$langFile.':'.$llKey) ?? '');
          if (strlen($temp) > 0) {
            $message = $temp;
          }
        }
        $langMarkers['###LLL:'.$marker.'###'] = $message;
      }
    }
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $langMarkers);
  }

  /**
   * Substitutes markers
   *        ###selected_[fieldname]_[value]###
   *        ###checked_[fieldname]_[value]###
   * in $this->template.
   */
  protected function fillSelectedMarkers(): void {
    $values = $this->gp;
    unset($values['randomID'], $values['submitted'], $values['removeFile'], $values['removeFileField'], $values['submitField'], $values['formErrors']);

    $markers = $this->getSelectedMarkers($values);
    $markers = array_merge($markers, $this->getSelectedMarkers($this->gp, 0, 'checked_'));
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);

    $this->template = preg_replace('/###(selected|checked)_.*?###/i', '', $this->template) ?? '';
  }

  /**
   * Fills the markers ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### with the stored values from session.
   */
  protected function fillStartEndBlock(): void {
    $markers = [
      '###FORM_STARTBLOCK###' => $this->globals->getSession()?->get('startblock') ?? '',
      '###FORM_ENDBLOCK###' => $this->globals->getSession()?->get('endblock') ?? '',
    ];
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
  }

  /**
   * Substitutes markers defined in TypoScript in $this->template.
   */
  protected function fillTypoScriptMarkers(): void {
    $markers = [];
    if (is_array($this->settings['markers.'])) {
      foreach ($this->settings['markers.'] as $name => $options) {
        if (!strstr($name, '.') && strstr($this->template, '###'.$name.'###')) {
          $markers['###'.$name.'###'] = $this->utilityFuncs->getSingle($this->settings['markers.'], $name);
        }
      }
    }
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
  }

  /**
   * Substitutes markers
   *        ###value_[fieldname]###
   *        ###VALUE_[FIELDNAME]###
   *        ###[fieldname]###
   *        ###[FIELDNAME]###
   * in $this->template.
   */
  protected function fillValueMarkers(): void {
    $this->disableEncodingFields = [];
    if (isset($this->settings['disableEncodingFields']) && (bool) $this->settings['disableEncodingFields']) {
      $this->disableEncodingFields = explode(',', $this->utilityFuncs->getSingle($this->settings, 'disableEncodingFields'));
    }
    $markers = $this->getValueMarkers($this->gp);
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);

    // remove remaining VALUE_-markers
    // needed for nested markers like ###LLL:tx_myextension_table.field1.i.###value_field1###### to avoid wrong marker removal if field1 isn't set
    $this->template = preg_replace('/###value_.*?###/i', '', $this->template) ?? '';
  }

  /**
   * @param array<string, mixed> $values
   *
   * @return array<string, mixed>
   */
  protected function getSelectedMarkers(array $values, int $level = 0, string $prefix = 'selected_'): array {
    $markers = [];
    $activeString = 'selected="selected"';
    if ('checked_' === substr($prefix, 0, 8)) {
      $activeString = 'checked="checked"';
    }
    if (is_array($values)) {
      foreach ($values as $k => $v) {
        $currPrefix = $prefix;
        if (0 === $level) {
          $currPrefix .= $k;
        } else {
          $currPrefix .= '|'.$k;
        }
        if (is_array($v)) {
          ++$level;
          $markers = array_merge($markers, $this->getSelectedMarkers($v, $level, $currPrefix));
          --$level;
        } else {
          $v = htmlspecialchars(strval($v));
          $markers['###'.$currPrefix.'_'.$v.'###'] = $activeString;
          $markers['###'.strtoupper($currPrefix).'###'] = $markers['###'.$currPrefix.'_'.$v.'###'];
        }
      }
    }

    return $markers;
  }

  /**
   * @param array<string, mixed> $imgConf
   * @param array<string, mixed> $fileInfo
   */
  protected function getThumbnail(array &$imgConf, array &$fileInfo): string {
    $imgConfig = [];
    $fileConfig = [];

    if (is_array($imgConf['image.'] ?? null)) {
      $imgConfig = (array) $imgConf['image.'];
    }
    $filename = $fileInfo['name'];
    $imgConf['image'] = 'IMAGE';
    if (!isset($imgConfig['altText'])) {
      $imgConfig['altText'] = $filename;
    }
    if (!isset($imgConfig['titleText'])) {
      $imgConfig['titleText'] = $filename;
    }
    $relPath = substr($fileInfo['uploaded_folder'].$fileInfo['uploaded_name'], 1);

    $imgConfig['file'] = $relPath;
    if (is_array($imgConfig['file.'] ?? null)) {
      $fileConfig = (array) $imgConfig['file.'];
    }

    if (!isset($fileConfig['width']) && !isset($fileConfig['height'])) {
      $fileConfig['width'] = '100m';
      $fileConfig['height'] = '100m';
    }

    $imgConfig['file.'] = $fileConfig;
    $imgConf['image.'] = $imgConfig;

    return $this->cObj?->cObjGetSingle('IMAGE', (array) $imgConf['image.']) ?? '';
  }

  /**
   * @param array<string, mixed> $values
   *
   * @return array<string, string>
   */
  protected function getValueMarkers(array $values, int $level = 0, string $prefix = 'value_', bool $doEncode = true): array {
    $markers = [];

    $arrayValueSeparator = $this->utilityFuncs->getSingle($this->settings, 'arrayValueSeparator');
    if (0 === strlen($arrayValueSeparator)) {
      $arrayValueSeparator = ',';
    }
    if (is_array($values)) {
      foreach ($values as $k => $v) {
        $currPrefix = $prefix;
        if (0 === $level) {
          $currPrefix .= $k;
        } else {
          $currPrefix .= '|'.$k;
        }
        if (is_array($v)) {
          ++$level;
          $markers = array_merge($markers, $this->getValueMarkers($v, $level, $currPrefix));
          --$level;
        } else {
          if ($doEncode) {
            if (!in_array($k, $this->disableEncodingFields)) {
              $v = htmlspecialchars(strval($v));
            }
          }
          $markers['###'.$currPrefix.'###'] = trim(strval($v));
          $markers['###'.strtoupper($currPrefix).'###'] = $markers['###'.$currPrefix.'###'];
        }
      }
    }

    return $markers;
  }

  protected function handleHasTranslationSubpartCondition(string $condition): bool {
    $translation = $this->utilityFuncs->getTranslatedMessage($this->langFiles, $condition);

    return strlen($translation) > 0;
  }

  protected function handleIfSubpartCondition(string $condition): bool {
    return $this->utilityFuncs->getConditionResult($condition, $this->gp);
  }

  protected function handleIssetSubpartCondition(string $condition): bool {
    $fieldname = $condition;
    $negate = false;
    if ('!' === substr($condition, 0, 1)) {
      $fieldname = substr($condition, 1);
      $negate = true;
    }
    $value = $this->utilityFuncs->getGlobal($fieldname, $this->gp);
    if (is_array($value)) {
      $result = (!empty($value));
    } else {
      $result = (strlen(trim(strval($value))) > 0);
    }
    if ($negate) {
      $result = !$result;
    }

    return $result;
  }

  /**
   * Returns the global TypoScript settings of Formhandler.
   *
   * @return array<string, mixed> The settings
   */
  protected function parseSettings(): array {
    return (array) ($this->globals->getSession()?->get('settings') ?? []);
  }

  /**
   * Reads the translation file entered in TS setup.
   */
  protected function readMasterTemplates(): void {
    $this->masterTemplates = [];
    if (isset($this->settings['masterTemplateFile']) && !isset($this->settings['masterTemplateFile.'])) {
      array_push($this->masterTemplates, $this->utilityFuncs->resolveRelPathFromSiteRoot(strval($this->settings['masterTemplateFile'])));
    } elseif (isset($this->settings['masterTemplateFile'], $this->settings['masterTemplateFile.'])) {
      array_push(
        $this->masterTemplates,
        $this->utilityFuncs->resolveRelPathFromSiteRoot($this->utilityFuncs->getSingle($this->settings, 'masterTemplateFile'))
      );
    } elseif (isset($this->settings['masterTemplateFile.']) && is_array($this->settings['masterTemplateFile.'])) {
      foreach ($this->settings['masterTemplateFile.'] as $key => $masterTemplate) {
        $key = strval($key);
        if (false === strpos($key, '.')) {
          if (is_array($this->settings['masterTemplateFile.'][$key.'.'] ?? null)) {
            array_push(
              $this->masterTemplates,
              $this->utilityFuncs->resolveRelPathFromSiteRoot($this->utilityFuncs->getSingle($this->settings['masterTemplateFile.'], $key))
            );
          } else {
            array_push($this->masterTemplates, $this->utilityFuncs->resolveRelPathFromSiteRoot($masterTemplate));
          }
        }
      }
    }
  }

  protected function replaceMarkersFromMaster(): void {
    $fieldMarkers = [];
    foreach ($this->masterTemplates as $idx => $masterTemplate) {
      $masterTemplateCode = GeneralUtility::getURL($this->utilityFuncs->resolvePath($masterTemplate)) ?: '';
      $matches = [];
      preg_match_all('/###(field|master)_([^#]*)###/', $masterTemplateCode, $matches);
      if (!empty($matches[0])) {
        $subparts = array_unique($matches[0]);

        /** @var array<string, string> */
        $subpartsCodes = [];
        if (is_array($subparts)) {
          /** @var string $subpart */
          foreach ($subparts as $subpart) {
            $subpartKey = str_replace('#', '', $subpart);
            $code = $this->markerBasedTemplateService->getSubpart($masterTemplateCode, $subpart);
            if (!empty($code)) {
              $subpartsCodes[$subpartKey] = $code;
            }
          }
        }
        foreach ($subpartsCodes as $subpart => $code) {
          $matchesSlave = [];
          preg_match_all('/###'.$subpart.'(###|_([^#]*)###)/', $this->template, $matchesSlave);
          if (!empty($matchesSlave[0])) {
            foreach ($matchesSlave[0] as $key => $markerName) {
              $fieldName = $matchesSlave[2][$key];
              $params = [];
              if (strpos($fieldName, ';')) {
                $parts = explode(';', $fieldName);
                $fieldName = array_shift($parts);
                $params = explode(',', array_shift($parts) ?? '');
              }
              if ($fieldName) {
                $markers = [
                  '###fieldname###' => $fieldName,
                  '###formValuesPrefix###' => $this->globals->getFormValuesPrefix(),
                ];
                foreach ($params as $paramKey => $paramValue) {
                  $markers['###param'.(++$paramKey).'###'] = $paramValue;
                }
                $replacedCode = $this->markerBasedTemplateService->substituteMarkerArray($code, $markers);
              } else {
                $replacedCode = $code;
              }
              $fieldMarkers[$markerName] = $replacedCode;
            }
          }
        }
      }
    }
    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $fieldMarkers);
  }

  /**
   * Copies the subparts ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### and stored them in session.
   * This is needed to replace the markers ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### in the next steps.
   */
  protected function storeStartEndBlock(): void {
    $startblock = $this->globals->getSession()?->get('startblock');
    $endblock = $this->globals->getSession()?->get('endblock');
    if (empty($startblock)) {
      $startblock = $this->markerBasedTemplateService->getSubpart($this->template, '###FORM_STARTBLOCK###');
    }
    if (empty($endblock)) {
      $endblock = $this->markerBasedTemplateService->getSubpart($this->template, '###FORM_ENDBLOCK###');
    }
    $this->globals->getSession()?->setMultiple(['startblock' => $startblock, 'endblock' => $endblock]);
  }

  /**
   * Use or remove subparts with [IF|ISSET|HAS_TRANSLATION]_[fieldname]=[value] patterns.
   *
   * @author  Arno Dudek <webmaster@adgrafik.at>
   * @author  Reinhard Führicht <rf@typoheads.at>
   */
  protected function substituteConditionalSubparts(string $type): void {
    $type = strtolower($type);

    $pattern = '/(\<\!\-\-\s*)?(###'.$type.'_+([^#]*)_*###)([^\-]*\-\-\>)?/i';
    preg_match_all($pattern, $this->template, $matches);
    if (is_array($matches[0])) {
      $resultCount = count($matches[0]);
      for ($i = 0; $i < $resultCount; $i = $i + 2) {
        $conditionString = $matches[3][$i];
        $fullMarkerName = $matches[0][$i];
        $fullEndMarker = $matches[0][$i + 1];
        $conditions = (preg_split('/\s*(\|\||&&)\s*/i', $conditionString, -1, PREG_SPLIT_DELIM_CAPTURE) ?: []);
        $operator = null;
        $finalConditionResult = false;
        $count = 0;

        foreach ($conditions as $condition) {
          $conditionResult = '';
          if ('||' === $condition || '&&' === $condition) {
            $operator = $condition;
          } else {
            switch ($type) {
              case 'if':
                $conditionResult = $this->handleIfSubpartCondition($condition);

                break;

              case 'isset':
                $conditionResult = $this->handleIssetSubpartCondition($condition);

                break;

              case 'has_translation':
                $conditionResult = $this->handleHasTranslationSubpartCondition($condition);

                break;

              default:
                $this->utilityFuncs->throwException('Unsupported conditional subpart type: '.$type);

                break;
            }
          }
          if (0 === $count) {
            $finalConditionResult = $conditionResult;
          } elseif ('&&' === $operator) {
            $finalConditionResult = ($finalConditionResult && $conditionResult);
          } elseif ('||' === $operator) {
            $finalConditionResult = ($finalConditionResult || $conditionResult);
          } else {
            $finalConditionResult = $conditionResult;
          }
          ++$count;
        }
        $write = (bool) $finalConditionResult;
        $replacement = '';
        if ($write) {
          $replacement = '${1}';
        }
        $fullMarkerName = preg_quote($fullMarkerName, '/');
        $fullEndMarker = preg_quote($fullEndMarker, '/');
        $pattern = '/'.$fullMarkerName.'(.*?)'.$fullEndMarker.'/ism';
        $this->template = preg_replace($pattern, $replacement, $this->template) ?? '';
      }
    }
  }

  private static function inline2TempFile(string $str, string $ext): string {
    // Create filename / tags:
    $script = '';

    switch ($ext) {
      case 'js':
        $script = 'typo3temp/javascript_'.substr(md5($str), 0, 10).'.js';

        break;

      case 'css':
        $script = 'typo3temp/stylesheet_'.substr(md5($str), 0, 10).'.css';

        break;
    }

    // Write file:
    if ($script) {
      if (!@is_file(Environment::getPublicPath().'/'.$script)) {
        GeneralUtility::writeFile(Environment::getPublicPath().'/'.$script, $str);
      }
    }

    return $script;
  }
}
