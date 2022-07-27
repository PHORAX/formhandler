<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Generator\AbstractGenerator;
use Typoheads\Formhandler\Mailer\TYPO3Mailer;
use Typoheads\Formhandler\View\AbstractView;

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
 * Finisher to send mails after successful form submission.
 *
 * A sample configuration looks like this:
 *
 * <code>
 * finishers.2.class = Tx_Formhandler_Finisher_Mail
 * finishers.2.config.limitMailsToUser = 5
 * finishers.2.config.checkBinaryCfLr = firstname,text,email
 * finishers.2.config.admin.header =
 * finishers.2.config.admin.to_email = rf@typoheads.at
 * finishers.2.config.admin.to_name = Reinhard Führicht
 * finishers.2.config.admin.subject = SingleStep Request
 * finishers.2.config.admin.sender_email = email
 * finishers.2.config.admin.sender_name = lastname
 * finishers.2.config.admin.replyto_email = email
 * finishers.2.config.admin.replyto_name = lastname
 * finishers.2.config.admin.cc_email = office@host.com
 * finishers.2.config.admin.htmlEmailAsAttachment = 1
 * finishers.2.config.user.header = ...
 * finishers.2.config.user.to_email = email
 * finishers.2.config.user.to_name = lastname
 * finishers.2.config.user.subject = Your SingleStep request
 * finishers.2.config.user.sender_email = rf@typoheads.at
 * finishers.2.config.user.sender_name = Reinhard Führicht
 * finishers.2.config.user.replyto_email = rf@typoheads.at
 * finishers.2.config.user.replyto_name = TEXT
 * finishers.2.config.user.replyto_name.value = Reinhard Führicht
 * finishers.2.config.user.cc_email = controlling@host.com
 * finishers.2.config.user.cc_name = Contact Request
 *
 * # sends only plain text mails and adds the HTML mail as attachment
 * finishers.2.config.user.htmlEmailAsAttachment = 1
 *
 * # attaches static files or files uploaded via a form field
 * finishers.2.config.user.attachment = fileadmin/files/file.txt,picture
 *
 * # attaches a PDF file with submitted values
 * finishers.2.config.user.attachPDF.class = Tx_Formhandler_Generator_TcPdf
 * finishers.2.config.user.attachPDF.exportFields = firstname,lastname,email,interests,pid,submission_date,ip
 *
 * #configure how the attached files are prefixes (PDF/HTML).
 * # both files prefixed equally:
 * finishers.2.config.user.filePrefix = MyContactForm_
 *
 * # different prefixes for the files.
 * finishers.2.config.html = MyContactForm_
 * finishers.2.config.pdf = MyContactFormPDF_
 * </code>
 */
class Mail extends AbstractFinisher {
  /** @var array<string, mixed> */
  protected array $emailSettings = [];

  private TYPO3Mailer $emailObj;

  /**
   * Method to set GET/POST for this class and load the configuration.
   */
  public function init(array $gp, array $tsConfig): void {
    $this->gp = $gp;
    $this->settings = $tsConfig;
  }

  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    // send emails
    $this->initMailer('admin');
    $this->sendMail('admin');
    $this->initMailer('user');
    $this->sendMail('user');

    return $this->gp;
  }

  /**
   * Explodes the given list seperated by $sep. Substitutes values with according value in GET/POST, if set.
   *
   * @param array<int|string, mixed>|string $list
   *
   * @return array<int|string, mixed>
   */
  protected function explodeList(array|string $list, string $sep = ','): array {
    if (!is_array($list)) {
      $items = GeneralUtility::trimExplode($sep, $list);
      $splitArray = [];
      foreach ($items as $idx => $item) {
        if (isset($this->gp[$item])) {
          array_push($splitArray, $this->gp[$item]);
        } else {
          array_push($splitArray, $item);
        }
      }
    } else {
      $splitArray = $list;
    }

    return $splitArray;
  }

  /**
   * Substitutes markers like ###LLL:langKey### in given TypoScript settings array.
   *
   * @param array<string, mixed> &$settings The E-Mail settings
   */
  protected function fillLangMarkersInSettings(array &$settings): void {
    /** @var MarkerBasedTemplateService $templateService */
    $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

    foreach ($settings as &$value) {
      if (isset($value) && is_array($value)) {
        $this->fillLangMarkersInSettings($value);
      } else {
        $langMarkers = $this->utilityFuncs->getFilledLangMarkers($value, $this->globals->getLangFiles());
        if (!empty($langMarkers)) {
          $value = $templateService->substituteMarkerArray($value, $langMarkers);
        }
      }
    }
  }

  /**
   * Fetches the global TypoScript settings of the Formhandler.
   *
   * @return array<string, mixed> The settings
   */
  protected function getSettings(): array {
    return $this->configuration->getSettings();
  }

  protected function initMailer(string $type): void {
    // init mailer object
    $globalSettings = $this->globals->getSettings();
    if (isset($this->settings['mailer.']) && is_array($this->settings['mailer.'])) {
      $emailClass = $this->utilityFuncs->getPreparedClassName($this->settings['mailer.'], 'Mailer\TYPO3Mailer');
    } elseif (isset($globalSettings['mailer.']) && is_array($globalSettings['mailer.'])) {
      $emailClass = $this->utilityFuncs->getPreparedClassName($globalSettings['mailer.'], 'Mailer\TYPO3Mailer');
    } else {
      $emailClass = $this->utilityFuncs->prepareClassName('\\Typoheads\\Formhandler\\Mailer\\TYPO3Mailer');
    }

    /** @var TYPO3Mailer $emailObj */
    $emailObj = GeneralUtility::makeInstance($emailClass);

    $this->emailObj = $emailObj;
    $this->emailObj->init($this->gp, (array) (((array) ($this->settings['mailer.'] ?? []))['config.'] ?? []));

    $this->emailSettings = $this->parseEmailSettings($this->settings, $type);

    // Defines default values
    $defaultOptions = [
      'templateFile' => 'template_file',
      'langFile' => 'lang_file',
    ];
    foreach ($defaultOptions as $key => $option) {
      $fileName = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], $option);
      if ($fileName) {
        $this->settings[$key] = $fileName;
      }
    }

    // Unset unnecessary variables.
    unset($this->settings[$type.'.']);
  }

  /**
   * Parses the email settings in flexform and stores them in an array.
   *
   * @param array<string, mixed> $tsConfig The TypoScript configuration
   * @param string               $type     (admin|user)
   *
   * @return array<string, mixed> The parsed email settings
   */
  protected function parseEmailSettings(array $tsConfig, string $type): array {
    $options = [
      'disable',
      'checkBinaryCfLr',
      'header',
      'filePrefix',
      'subject',
      'sender_email',
      'sender_name',
      'replyto_email',
      'replyto_name',
      'cc_email',
      'cc_name',
      'bcc_email',
      'bcc_name',
      'to_email',
      'to_name',
      'return_path',
      'attachment',
      'embedFiles',
      'attachGeneratedFiles',
      'deleteGeneratedFiles',
      'htmlEmailAsAttachment',
      'plain.',
      'html.',
      'templateFile',
    ];

    return $this->parseEmailSettingsByType((array) ($tsConfig[$type.'.'] ?? []), $type, $options);
  }

  /**
   * Parses the email settings in flexform of a specific type (admin|user].
   *
   * @param array<string, mixed> $currentSettings The current settings array containing the settings made via TypoScript
   * @param string               $type            (admin|user)
   * @param array<int, mixed>    $optionsToParse  array containing all option names to parse
   *
   * @return array<string, mixed> The parsed email settings
   */
  protected function parseEmailSettingsByType(array $currentSettings, string $type, array $optionsToParse = []): array {
    $typeUpper = strtoupper($type);
    $section = 'sEMAIL'.$typeUpper;
    $emailSettings = [];
    foreach ($optionsToParse as $idx => $option) {
      $option = strval($option);
      $value = $this->utilityFuncs->pi_getFFvalue((array) ($this->cObj->data['pi_flexform'] ?? []), $option, $section);
      if (strlen($value) > 0) {
        $emailSettings[$option] = $value;
        if (isset($this->gp[$value])) {
          $emailSettings[$option] = $this->gp[$value];
        }
      } else {
        switch ($option) {
          case 'to_email':
          case 'to_name':
          case 'sender_email':
          case 'replyto_email':
          case 'cc_email':
          case 'bcc_email':
          case 'return_path':
            $emailSettings[$option] = $this->parseList($currentSettings, $type, $option);

            break;

          case 'disable':
          case 'checkBinaryCfLr':
          case 'header':
          case 'subject':
          case 'sender_name':
          case 'replyto_name':
          case 'cc_name':
          case 'bcc_name':
          case 'templateFile':
            $emailSettings[$option] = $this->parseValue($currentSettings, $type, $option);

            break;

          case 'attachment':
            $emailSettings[$option] = $this->parseFilesList($currentSettings, $type, $option);

            break;

          case 'embedFiles':
            $emailSettings[$option] = $this->parseEmbedFilesList($currentSettings);

            break;

          case 'attachPDF':
          case 'attachGeneratedFiles':
            if (isset($currentSettings['attachGeneratedFiles.']) && is_array($currentSettings['attachGeneratedFiles.'])) {
              foreach ($currentSettings['attachGeneratedFiles.'] as $options) {
                $generatorClass = $this->utilityFuncs->getPreparedClassName($options);
                if ($generatorClass) {
                  /** @var AbstractGenerator $generator */
                  $generator = GeneralUtility::makeInstance($generatorClass);
                  $generator->init($this->gp, $options['config.']);
                  $generator->getLink([]);
                  $file = strval($generator->process());
                  $emailSettings['attachGeneratedFiles'] .= $file.',';
                }
              }
              if (isset($emailSettings['attachGeneratedFiles']) && !empty($emailSettings['attachGeneratedFiles']) && ',' === substr(strval($emailSettings['attachGeneratedFiles']), strlen(strval($emailSettings['attachGeneratedFiles'])) - 1)) {
                $emailSettings['attachGeneratedFiles'] = substr(strval($emailSettings['attachGeneratedFiles']), 0, strlen(strval($emailSettings['attachGeneratedFiles'])) - 1);
              }
              unset($currentSettings['attachGeneratedFiles.']);
              $currentSettings['attachGeneratedFiles'] = $emailSettings['attachGeneratedFiles'];
            } elseif (isset($currentSettings['attachGeneratedFiles'])) {
              $emailSettings['attachGeneratedFiles'] = $currentSettings['attachGeneratedFiles'];
            }

            break;

          case 'htmlEmailAsAttachment':
            $htmlEmailAsAttachment = $this->utilityFuncs->getSingle($currentSettings, 'htmlEmailAsAttachment');
            if (1 === (int) $htmlEmailAsAttachment) {
              $emailSettings['htmlEmailAsAttachment'] = 1;
            }

            break;

          case 'deleteGeneratedFiles':
            $htmlEmailAsAttachment = $this->utilityFuncs->getSingle($currentSettings, 'deleteGeneratedFiles');
            if (1 === (int) $htmlEmailAsAttachment) {
              $emailSettings['deleteGeneratedFiles'] = 1;
            }

            break;

          case 'filePrefix':
            $filePrefix = $this->utilityFuncs->getSingle($currentSettings, 'filePrefix');
            if (strlen($filePrefix) > 0) {
              $emailSettings['filePrefix'] = $filePrefix;
            }

            break;

          case 'plain.':
            if (isset($currentSettings['plain.'])) {
              $emailSettings['plain.'] = $currentSettings['plain.'];
            }

            break;

          case 'html.':
            if (isset($currentSettings['html.'])) {
              $emailSettings['html.'] = $currentSettings['html.'];
            }

            break;
        }
      }
    }
    $this->fillLangMarkersInSettings($emailSettings);

    return $emailSettings;
  }

  /**
   * Parses a list of file names or field names set in TypoScript to embed in the mail.
   *
   * @param array<string, mixed> $settings The settings array containing the mail settings
   *
   * @return array<string, mixed>
   */
  protected function parseEmbedFilesList(array $settings): array {
    $cids = [];
    if (isset($settings['embedFiles.']) && is_array($settings['embedFiles.'])) {
      foreach ($settings['embedFiles.'] as $key => $embedFileSettings) {
        if (false === strpos($key, '.')) {
          $embedFile = $this->utilityFuncs->getSingle($settings['embedFiles.'], $key);
          if (strlen($embedFile) > 0) {
            if (!strstr($embedFile, $this->utilityFuncs->getDocumentRoot())) {
              $embedFile = $this->utilityFuncs->getDocumentRoot().'/'.$embedFile;
            }
            $embedFile = $this->utilityFuncs->sanitizePath($embedFile);
            $cids[$key] = $this->emailObj->embed($embedFile);
          } else {
            $this->utilityFuncs->debugMessage('attachment_not_found', [$embedFile], 2);
          }
        }
      }
    }

    return $cids;
  }

  /**
   * Parses a list of file names or field names set in TypoScript and overrides it with setting in plugin record if set.
   *
   * @param array<string, mixed> $settings The settings array containing the mail settings
   * @param string               $type     admin|user
   * @param string               $key      The key to parse in the settings array
   *
   * @return array<int, string>
   */
  protected function parseFilesList(array $settings, string $type, string $key): array {
    $files = GeneralUtility::trimExplode(',', $this->utilityFuncs->getSingle($settings, $key));

    $parsed = [];
    $sessionFiles = (array) ($this->globals->getSession()?->get('files') ?? []);
    foreach ($files as $idx => $file) {
      if (isset($sessionFiles[$file]) && is_array($sessionFiles[$file])) {
        foreach ($sessionFiles[$file] as $subIdx => $uploadedFile) {
          array_push($parsed, $uploadedFile['uploaded_path'].$uploadedFile['uploaded_name']);
        }
      } elseif (file_exists($file)) {
        array_push($parsed, $file);
      } elseif (file_exists(strval(GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT')).'/'.$file)) {
        array_push($parsed, strval(GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT')).'/'.$file);
      } elseif (strlen($file) > 0) {
        $this->utilityFuncs->debugMessage('attachment_not_found', [$file], 2);
      }
    }

    return $parsed;
  }

  /**
   * Parses a setting in TypoScript and overrides it with setting in plugin record if set.
   * The settings contains a list of values or a TS object.
   *
   * @param array<string, mixed> $settings The settings array containing the mail settings
   * @param string               $type     admin|user
   * @param string               $key      The key to parse in the settings array
   *
   * @return array<int|string, mixed>
   */
  protected function parseList(array $settings, string $type, string $key): array {
    if (isset($settings[$type]) && is_array($settings[$type]) && isset($settings[$type][$key])) {
      $parsed = $this->explodeList($settings[$type][$key]);
    } elseif (isset($settings[$key.'.']) && is_array($settings[$key.'.'])) {
      $parsed = $this->explodeList($this->utilityFuncs->getSingle($settings, $key));
    } else {
      $parsed = $this->explodeList(strval($settings[$key]));
    }

    return $parsed;
  }

  /**
   * Substitutes values with according value in GET/POST, if set.
   */
  protected function parseSettingValue(string $value): string {
    if (isset($this->gp[$value])) {
      $parsed = strval($this->gp[$value]);
    } else {
      $parsed = $value;
    }

    return $parsed;
  }

  /**
   * Returns the final template code for given mode and suffix with substituted markers.
   *
   * @param string $mode   user/admin
   * @param string $suffix plain/html
   *
   * @return string The template code
   */
  protected function parseTemplate(string $mode, string $suffix): string {
    // set view
    $viewClass = '\Typoheads\Formhandler\View\Mail';
    if (isset($this->settings['view'])) {
      $viewClass = $this->utilityFuncs->getSingle($this->settings, 'view');
    }
    $viewClass = $this->utilityFuncs->prepareClassName($viewClass);

    /** @var AbstractView $view */
    $view = GeneralUtility::makeInstance($viewClass);

    $view->setLangFiles($this->globals->getLangFiles());
    $view->setPredefined($this->predefined);
    $view->setComponentSettings($this->emailSettings);
    $templateCode = $this->globals->getTemplateCode();
    if (isset($this->settings['templateFile'])) {
      $templateCode = $this->utilityFuncs->readTemplateFile('', $this->settings);
    }
    if (isset($this->settings[$mode]) && is_array($this->settings[$mode]) && isset($this->settings[$mode]['templateFile'])) {
      $templateCode = $this->utilityFuncs->readTemplateFile('', $this->settings[$mode]);
    }

    $view->setTemplate($templateCode, 'EMAIL_'.strtoupper($mode).'_'.strtoupper($suffix).$this->globals->getTemplateSuffix());
    if (!$view->hasTemplate()) {
      $view->setTemplate($templateCode, 'EMAIL_'.strtoupper($mode).'_'.strtoupper($suffix));
      if (!$view->hasTemplate()) {
        $this->utilityFuncs->debugMessage('no_mail_template', [$mode, $suffix], 2);
      }
    }

    return $view->render($this->gp, ['mode' => $mode, 'suffix' => $suffix]);
  }

  /**
   * Parses a setting in TypoScript and overrides it with setting in plugin record if set.
   * The settings contains a single value or a TS object.
   *
   * @param array<string, mixed> $settings The settings array containing the mail settings
   * @param string               $type     admin|user
   * @param string               $key      The key to parse in the settings array
   */
  protected function parseValue(array $settings, string $type, string $key): string {
    if (isset($settings[$type]) && is_array($settings[$type]) && isset($settings[$type][$key])) {
      $parsed = $this->parseSettingValue($settings[$type][$key]);
    } elseif (isset($settings[$key.'.']) && is_array($settings[$key.'.'])) {
      $settings[$key.'.']['gp'] = $this->gp;
      $parsed = $this->utilityFuncs->getSingle($settings, $key);
    } else {
      $parsed = $this->parseSettingValue(strval($settings[$key] ?? ''));
    }

    return $parsed;
  }

  /**
   * Sends mail according to given type.
   *
   * @param string $type (admin|user)
   */
  protected function sendMail(string $type): bool {
    $doSend = true;
    if (1 === (int) $this->utilityFuncs->getSingle($this->emailSettings, 'disable')) {
      $this->utilityFuncs->debugMessage('mail_disabled', [$type]);
      $doSend = false;
    }

    $plain = $this->parseTemplate($type, 'plain');
    if (strlen(trim($plain)) > 0) {
      $template['plain'] = $plain;
    }
    $html = $this->parseTemplate($type, 'html');
    if (strlen(trim($html)) > 0) {
      $template['html'] = $html;
    }

    // set e-mail options
    $this->emailObj->setSubject(strval($this->emailSettings['subject'] ?? ''));

    if (isset($this->emailSettings['sender_email']) && is_array($this->emailSettings['sender_email'])) {
      $sender = implode(',', $this->emailSettings['sender_email']);
    } else {
      $sender = strval($this->emailSettings['sender_email'] ?? '');
    }

    if (isset($this->emailSettings['sender_name']) && is_array($this->emailSettings['sender_name'])) {
      $senderName = implode(',', $this->emailSettings['sender_name']);
    } else {
      $senderName = strval($this->emailSettings['sender_name'] ?? '');
    }

    $this->emailObj->setSender($sender, $senderName);

    if (isset($this->emailSettings['replyto_email']) && is_array($this->emailSettings['replyto_email'])) {
      $replyto = implode(',', $this->emailSettings['replyto_email']);
    } else {
      $replyto = strval($this->emailSettings['replyto_email'] ?? '');
    }

    if (isset($this->emailSettings['replyto_name']) && is_array($this->emailSettings['replyto_name'])) {
      $replytoName = implode(',', $this->emailSettings['replyto_name']);
    } else {
      $replytoName = strval($this->emailSettings['replyto_name'] ?? '');
    }

    $this->emailObj->setReplyTo($replyto, $replytoName);

    $cc = $this->emailSettings['cc_email'];
    if (!is_array($cc)) {
      $cc = GeneralUtility::trimExplode(',', strval($cc));
    }

    $ccName = $this->emailSettings['cc_name'];
    if (!is_array($ccName)) {
      $ccName = GeneralUtility::trimExplode(',', strval($ccName));
    }

    foreach ($cc as $key => $email) {
      $name = '';
      if (isset($ccName[$key])) {
        $name = $ccName[$key];
      }
      if (strlen($email) > 0) {
        $this->emailObj->addCc($email, $name);
      }
    }

    $bcc = $this->emailSettings['bcc_email'];
    if (!is_array($bcc)) {
      $bcc = GeneralUtility::trimExplode(',', strval($bcc));
    }

    $bccName = $this->emailSettings['bcc_name'];
    if (!is_array($bccName)) {
      $bccName = GeneralUtility::trimExplode(',', strval($bccName));
    }
    foreach ($bcc as $key => $email) {
      $name = '';
      if (isset($bccName[$key])) {
        $name = $bccName[$key];
      }
      if (strlen($email) > 0) {
        $this->emailObj->addBcc($email, $name);
      }
    }

    if (isset($this->emailSettings['return_path']) && is_array($this->emailSettings['return_path'])) {
      $returnPath = implode(',', $this->emailSettings['return_path']);
    } else {
      $returnPath = strval($this->emailSettings['return_path'] ?? '');
    }

    if (0 === strlen(trim($returnPath))) {
      $returnPath = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
    }

    if (strlen(trim($returnPath)) > 0) {
      $this->emailObj->setReturnPath($returnPath);
    }

    if (isset($this->emailSettings['header'])) {
      $this->emailObj->addHeader(strval($this->emailSettings['header']));
    }

    if (isset($template['plain']) && strlen(trim(strval($template['plain']))) > 0) {
      $this->emailObj->setPlain($template['plain']);
    } else {
      $this->emailObj->setPlain('');
    }

    if (isset($template['html']) && strlen(trim($template['html'])) > 0) {
      if (isset($this->emailSettings['htmlEmailAsAttachment']) && (bool) $this->emailSettings['htmlEmailAsAttachment']) {
        $prefix = 'formhandler_';

        if (isset($this->emailSettings['filePrefix.']) && is_array($this->emailSettings['filePrefix.']) && isset($this->emailSettings['filePrefix.']['html'])) {
          $prefix = $this->emailSettings['filePrefix.']['html'];
        } elseif (isset($this->emailSettings['filePrefix'])) {
          $prefix = $this->emailSettings['filePrefix'];
        }
        $tmphtml = tempnam('typo3temp/', '/'.$prefix).'.html';
        $tmphtml = str_replace('.tmp', '', $tmphtml);
        $tmphandle = fopen($tmphtml, 'wb');
        if ($tmphandle) {
          fwrite($tmphandle, $template['html']);
          fclose($tmphandle);
          $this->utilityFuncs->debugMessage('adding_html', [], 1, [$template['html']]);
          $this->emailObj->addAttachment($tmphtml);
        }
      } else {
        $this->emailObj->setHtml($template['html']);
      }
    }

    if (isset($this->emailSettings['attachment'])) {
      if (!is_array($this->emailSettings['attachment'])) {
        $this->emailSettings['attachment'] = GeneralUtility::trimExplode(',', strval($this->emailSettings['attachment']));
      }
      foreach ($this->emailSettings['attachment'] as $idx => $attachment) {
        if (strlen($attachment) > 0 && @file_exists($attachment)) {
          $this->emailObj->addAttachment($attachment);
        } else {
          $this->utilityFuncs->debugMessage('attachment_not_found', [$attachment], 2);
        }
      }
    }
    if (isset($this->emailSettings['attachGeneratedFiles'])) {
      $files = GeneralUtility::trimExplode(',', strval($this->emailSettings['attachGeneratedFiles']));
      $this->utilityFuncs->debugMessage('adding_generated_files', [], 1, $files);
      foreach ($files as $file) {
        $this->emailObj->addAttachment($file);
      }
    }

    // parse max count of mails to send
    $max = intval($this->utilityFuncs->getSingle($this->settings, 'limitMailsToUser'));
    if (!$max) {
      $max = 2;
    }
    if (!is_array($this->emailSettings['to_email'])) {
      $this->emailSettings['to_email'] = GeneralUtility::trimExplode(',', strval($this->emailSettings['to_email']));
    }
    reset($this->emailSettings['to_email']);

    // send e-mails
    $recipients = $this->emailSettings['to_email'];
    foreach ($recipients as $key => $recipient) {
      if (false === strpos($recipient, '@') || 0 === strpos($recipient, '@') || 0 === strlen(trim($recipient))) {
        unset($recipients[$key]);
      }
    }
    if (!empty($recipients) && count($recipients) > $max) {
      $recipients = array_slice($recipients, 0, $max);
    }
    $sent = false;
    if ($doSend && !empty($recipients)) {
      $sent = $this->emailObj->send($recipients);
    }
    if ($sent) {
      $this->utilityFuncs->debugMessage('mail_sent', [implode(',', $recipients)]);
    } else {
      $this->utilityFuncs->debugMessage('mail_not_sent', [implode(',', $recipients)], 2);
    }
    $this->utilityFuncs->debugMailContent($this->emailObj);
    if (isset($tmphtml)) {
      unlink($tmphtml);
    }

    // delete generated files
    if (isset($this->emailSettings['deleteGeneratedFiles']) && (bool) $this->emailSettings['deleteGeneratedFiles'] && isset($this->emailSettings['attachGeneratedFiles']) && (bool) $this->emailSettings['attachGeneratedFiles']) {
      $files = GeneralUtility::trimExplode(',', strval($this->emailSettings['attachGeneratedFiles']));
      foreach ($files as $file) {
        unlink($file);
      }
    }

    return $sent;
  }
}
