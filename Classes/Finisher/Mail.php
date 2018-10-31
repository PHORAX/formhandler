<?php
namespace Typoheads\Formhandler\Finisher;

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
class Mail extends AbstractFinisher
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {

        //send emails
        $this->initMailer('admin');
        $this->sendMail('admin');
        $this->initMailer('user');
        $this->sendMail('user');

        return $this->gp;
    }

    protected function initMailer($type)
    {
        //init mailer object
        $globalSettings = $this->globals->getSettings();
        if (is_array($this->settings['mailer.'])) {
            $emailClass = $this->utilityFuncs->getPreparedClassName($this->settings['mailer.'], 'Mailer\TYPO3Mailer');
        } elseif (is_array($globalSettings['mailer.'])) {
            $emailClass = $this->utilityFuncs->getPreparedClassName($globalSettings['mailer.'], 'Mailer\TYPO3Mailer');
        } else {
            $emailClass = $this->utilityFuncs->prepareClassName('\\Typoheads\\Formhandler\\Mailer\\TYPO3Mailer');
        }

        $this->emailObj = $this->componentManager->getComponent($emailClass);
        $this->emailObj->init($this->gp, $this->settings['mailer.']['config.']);

        $this->settings = $this->parseEmailSettings($this->settings, $type);

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
        unset($this->settings[$type . '.']);
    }

    /**
     * Returns the final template code for given mode and suffix with substituted markers.
     *
     * @param string $mode user/admin
     * @param string $suffix plain/html
     * @return string The template code
     */
    protected function parseTemplate($mode, $suffix)
    {
        $viewClass = $this->utilityFuncs->getSingle($this->settings, 'view');
        if (!$viewClass) {
            $viewClass = '\\Typoheads\\Formhandler\\View\\Mail';
        }

        /* @var $view Tx_Formhandler_AbstractView */
        $view = $this->componentManager->getComponent($viewClass);

        $view->setLangFiles($this->globals->getLangFiles());
        $view->setPredefined($this->predefined);
        $view->setComponentSettings($this->settings);
        $templateCode = $this->globals->getTemplateCode();
        if ($this->settings['templateFile']) {
            $templateCode = $this->utilityFuncs->readTemplateFile(false, $this->settings);
        }
        if ($this->settings[$mode]['templateFile']) {
            $templateCode = $this->utilityFuncs->readTemplateFile(false, $this->settings[$mode]);
        }

        $view->setTemplate($templateCode, ('EMAIL_' . strtoupper($mode) . '_' . strtoupper($suffix) . $this->globals->getTemplateSuffix()));
        if (!$view->hasTemplate()) {
            $view->setTemplate($templateCode, ('EMAIL_' . strtoupper($mode) . '_' . strtoupper($suffix)));
            if (!$view->hasTemplate()) {
                $this->utilityFuncs->debugMessage('no_mail_template', [$mode, $suffix], 2);
            }
        }

        return $view->render($this->gp, ['mode' => $mode, 'suffix' => $suffix]);
    }

    /**
     * Sends mail according to given type.
     *
     * @param string $type (admin|user)
     * @return void
     */
    protected function sendMail($type)
    {
        $doSend = true;
        if (intval($this->utilityFuncs->getSingle($this->settings[$type], 'disable')) === 1) {
            $this->utilityFuncs->debugMessage('mail_disabled', [$type]);
            $doSend = false;
        }

        $mailSettings = $this->settings[$type];
        $plain = $this->parseTemplate($type, 'plain');
        if (strlen(trim($plain)) > 0) {
            $template['plain'] = $plain;
        }
        $html = $this->parseTemplate($type, 'html');
        if (strlen(trim($html)) > 0) {
            $template['html'] = $html;
        }

        //set e-mail options
        $this->emailObj->setSubject($mailSettings['subject']);

        $sender = $mailSettings['sender_email'];
        if (isset($mailSettings['sender_email']) && is_array($mailSettings['sender_email'])) {
            $sender = implode(',', $mailSettings['sender_email']);
        }

        $senderName = $mailSettings['sender_name'];
        if (isset($mailSettings['sender_name']) && is_array($mailSettings['sender_name'])) {
            $senderName = implode(',', $mailSettings['sender_name']);
        }

        $this->emailObj->setSender($sender, $senderName);

        $replyto = $mailSettings['replyto_email'];
        if (isset($mailSettings['replyto_email']) && is_array($mailSettings['replyto_email'])) {
            $replyto = implode(',', $mailSettings['replyto_email']);
        }

        $replytoName = $mailSettings['replyto_name'];
        if (isset($mailSettings['replyto_name']) && is_array($mailSettings['replyto_name'])) {
            $replytoName = implode(',', $mailSettings['replyto_name']);
        }
        $this->emailObj->setReplyTo($replyto, $replytoName);

        $cc = $mailSettings['cc_email'];
        if (!is_array($cc)) {
            $cc = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $cc);
        }

        $ccName = $mailSettings['cc_name'];
        if (!is_array($ccName)) {
            $ccName = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $ccName);
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

        $bcc = $mailSettings['bcc_email'];
        if (!is_array($bcc)) {
            $bcc = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $bcc);
        }

        $bccName = $mailSettings['bcc_name'];
        if (!is_array($bccName)) {
            $bccName = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $bccName);
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

        $returnPath = $mailSettings['return_path'];
        if (isset($mailSettings['return_path']) && is_array($mailSettings['return_path'])) {
            $returnPath = implode(',', $mailSettings['return_path']);
        }
        if (strlen(trim($returnPath)) === 0) {
            $returnPath = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }

        $this->emailObj->setReturnPath($returnPath);

        if ($mailSettings['email_header']) {
            $this->emailObj->addHeader($mailSettings['header']);
        }

        if (strlen(trim($template['plain'])) > 0) {
            $this->emailObj->setPlain($template['plain']);
        } else {
            $this->emailObj->setPlain(null);
        }

        if (strlen(trim($template['html'])) > 0) {
            if ($mailSettings['htmlEmailAsAttachment']) {
                $prefix = 'formhandler_';
                if (isset($mailSettings['filePrefix.']['html'])) {
                    $prefix = $mailSettings['filePrefix.']['html'];
                } elseif (isset($mailSettings['filePrefix'])) {
                    $prefix = $mailSettings['filePrefix'];
                }
                $tmphtml = tempnam('typo3temp/', ('/' . $prefix)) . '.html';
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

        if (!is_array($mailSettings['attachment'])) {
            $mailSettings['attachment'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $mailSettings['attachment']);
        }
        foreach ($mailSettings['attachment'] as $idx => $attachment) {
            if (strlen($attachment) > 0 && @file_exists($attachment)) {
                $this->emailObj->addAttachment($attachment);
            } else {
                $this->utilityFuncs->debugMessage('attachment_not_found', [$attachment], 2);
            }
        }
        if ($mailSettings['attachGeneratedFiles']) {
            $files = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $mailSettings['attachGeneratedFiles']);
            $this->utilityFuncs->debugMessage('adding_generated_files', [], 1, $files);
            foreach ($files as $file) {
                $this->emailObj->addAttachment($file);
            }
        }

        //parse max count of mails to send
        $max = $this->utilityFuncs->getSingle($this->settings, 'limitMailsToUser');
        if (!$max) {
            $max = 2;
        }
        if (!is_array($mailSettings['to_email'])) {
            $mailSettings['to_email'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $mailSettings['to_email']);
        }
        reset($mailSettings['to_email']);

        //send e-mails
        $recipients = $mailSettings['to_email'];
        foreach ($recipients as $key => $recipient) {
            if (strpos($recipient, '@') === false || strpos($recipient, '@') === 0 || strlen(trim($recipient)) === 0) {
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
        if ($tmphtml) {
            unlink($tmphtml);
        }

        // delete generated files
        if ($mailSettings['deleteGeneratedFiles'] && $mailSettings['attachGeneratedFiles']) {
            $files = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $mailSettings['attachGeneratedFiles']);
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Explodes the given list seperated by $sep. Substitutes values with according value in GET/POST, if set.
     *
     * @param string $list
     * @param string $sep
     * @return array
     */
    protected function explodeList($list, $sep = ',')
    {
        if (!is_array($list)) {
            $items = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sep, $list);
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
     * Substitutes values with according value in GET/POST, if set.
     *
     * @param string $value
     * @return string
     */
    protected function parseSettingValue($value)
    {
        if (isset($this->gp[$value])) {
            $parsed = $this->gp[$value];
        } else {
            $parsed = $value;
        }
        return $parsed;
    }

    /**
     * Parses a setting in TypoScript and overrides it with setting in plugin record if set.
     * The settings contains a single value or a TS object.
     *
     * @param array $settings The settings array containing the mail settings
     * @param string $type admin|user
     * @param string $key The key to parse in the settings array
     * @return string
     */
    protected function parseValue($settings, $type, $key)
    {
        if (isset($this->emailSettings[$type][$key])) {
            $parsed = $this->parseSettingValue($this->emailSettings[$type][$key]);
        } elseif (isset($settings[$key . '.']) && is_array($settings[$key . '.'])) {
            $settings[$key . '.']['gp'] = $this->gp;
            $parsed = $this->utilityFuncs->getSingle($settings, $key);
        } else {
            $parsed = $this->parseSettingValue($settings[$key]);
        }
        return $parsed;
    }

    /**
     * Parses a setting in TypoScript and overrides it with setting in plugin record if set.
     * The settings contains a list of values or a TS object.
     *
     * @param array $settings The settings array containing the mail settings
     * @param string $type admin|user
     * @param string $key The key to parse in the settings array
     * @return string|array
     */
    protected function parseList($settings, $type, $key)
    {
        if (isset($this->emailSettings[$type][$key])) {
            $parsed = $this->explodeList($this->emailSettings[$type][$key]);
        } elseif (isset($settings[$key . '.']) && is_array($settings[$key . '.'])) {
            $parsed = $parsed = $this->explodeList($this->utilityFuncs->getSingle($settings, $key));
        } else {
            $parsed = $this->explodeList($settings[$key]);
        }
        return $parsed;
    }

    /**
     * Parses a list of file names or field names set in TypoScript and overrides it with setting in plugin record if set.
     *
     * @param array $settings The settings array containing the mail settings
     * @param string $type admin|user
     * @param string $key The key to parse in the settings array
     * @return string
     */
    protected function parseFilesList($settings, $type, $key)
    {
        $files = [];
        if (isset($settings[$key . '.']) && is_array($settings[$key . '.'])) {
            $files = $this->utilityFuncs->getSingle($settings, $key);
            $files = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $files);
        } elseif ($settings[$key]) {
            $files = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $settings[$key]);
        }
        $parsed = [];
        $sessionFiles = $this->globals->getSession()->get('files');
        foreach ($files as $idx => $file) {
            if (isset($sessionFiles[$file])) {
                foreach ($sessionFiles[$file] as $subIdx => $uploadedFile) {
                    array_push($parsed, $uploadedFile['uploaded_path'] . $uploadedFile['uploaded_name']);
                }
            } elseif (file_exists($file)) {
                array_push($parsed, $file);
            } elseif (file_exists(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/' . $file)) {
                array_push($parsed, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/' . $file);
            } elseif (strlen($file) > 0) {
                $this->utilityFuncs->debugMessage('attachment_not_found', [$file], 2);
            }
        }
        return $parsed;
    }

    /**
     * Parses a list of file names or field names set in TypoScript to embed in the mail.
     *
     * @param array $settings The settings array containing the mail settings
     * @return array
     */
    protected function parseEmbedFilesList($settings)
    {
        $cids = [];
        if (isset($settings['embedFiles.']) && is_array($settings['embedFiles.'])) {
            foreach ($settings['embedFiles.'] as $key => $embedFileSettings) {
                if (strpos($key, '.') === false) {
                    $embedFile = $this->utilityFuncs->getSingle($settings['embedFiles.'], $key);
                    if (strlen($embedFile) > 0) {
                        if (!strstr($embedFile, $this->utilityFuncs->getDocumentRoot())) {
                            $embedFile = $this->utilityFuncs->getDocumentRoot() . '/' . $embedFile;
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
     * Substitutes markers like ###LLL:langKey### in given TypoScript settings array.
     *
     * @param array &$settings The E-Mail settings
     * @return void
     */
    protected function fillLangMarkersInSettings(&$settings)
    {
        foreach ($settings as &$value) {
            if (isset($value) && is_array($value)) {
                $this->fillLangMarkersInSettings($value);
            } else {
                $langMarkers = $this->utilityFuncs->getFilledLangMarkers($value, $this->globals->getLangFiles());
                if (!empty($langMarkers)) {
                    $value = $this->markerBasedTemplateService->substituteMarkerArray($value, $langMarkers);
                }
            }
        }
    }

    /**
     * Fetches the global TypoScript settings of the Formhandler
     *
     * @return array The settings
     */
    protected function getSettings()
    {
        return $this->configuration->getSettings();
    }

    /**
     * Method to set GET/POST for this class and load the configuration
     *
     * @param array The GET/POST values
     * @param array The TypoScript configuration
     * @return void
     */
    public function init($gp, $tsConfig)
    {
        $this->gp = $gp;
        $this->settings = $tsConfig;
    }

    /**
     * Parses the email settings in flexform and stores them in an array.
     *
     * @param array The TypoScript configuration
     * @return array The parsed email settings
     */
    protected function parseEmailSettings($tsConfig, $type)
    {
        $emailSettings = $tsConfig;
        $options = [
            'filePrefix',
            'to_email',
            'subject',
            'sender_email',
            'sender_name',
            'replyto_email',
            'replyto_name',
            'cc_email',
            'cc_name',
            'bcc_email',
            'bcc_name',
            'to_name',
            'return_path',
            'attachment',
            'embedFiles',
            'attachGeneratedFiles',
            'deleteGeneratedFiles',
            'htmlEmailAsAttachment',
            'plain.',
            'html.'
        ];

        $emailSettings[$type] = $this->parseEmailSettingsByType($emailSettings[$type . '.'], $type, $options);

        return $emailSettings;
    }

    /**
     * Parses the email settings in flexform of a specific type (admin|user]
     *
     * @param array $currentSettings The current settings array containing the settings made via TypoScript
     * @param string $type (admin|user)
     * @param array $optionsToParse Array containing all option names to parse.
     * @return array The parsed email settings
     */
    protected function parseEmailSettingsByType($currentSettings, $type, $optionsToParse = [])
    {
        $typeUpper = strtoupper($type);
        $section = 'sEMAIL' . $typeUpper;
        $emailSettings = $currentSettings;
        foreach ($optionsToParse as $idx => $option) {
            $value = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], $option, $section);
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

                    case 'subject':
                    case 'sender_name':
                    case 'replyto_name':
                    case 'cc_name':
                    case 'bcc_name':
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
                                    $generator = $this->componentManager->getComponent($generatorClass);
                                    $generator->init($this->gp, $options['config.']);
                                    $generator->getLink([]);
                                    $file = $generator->process();
                                    $emailSettings['attachGeneratedFiles'] .= $file . ',';
                                }
                            }
                            if (substr($emailSettings['attachGeneratedFiles'], strlen($emailSettings['attachGeneratedFiles']) - 1) === ',') {
                                $emailSettings['attachGeneratedFiles'] = substr($emailSettings['attachGeneratedFiles'], 0, strlen($emailSettings['attachGeneratedFiles']) - 1);
                            }
                            unset($currentSettings['attachGeneratedFiles.']);
                            $currentSettings['attachGeneratedFiles'] = $emailSettings['attachGeneratedFiles'];
                        } elseif ($currentSettings['attachGeneratedFiles']) {
                            $emailSettings['attachGeneratedFiles'] = $currentSettings['attachGeneratedFiles'];
                        }
                        break;

                    case 'htmlEmailAsAttachment':
                        $htmlEmailAsAttachment = $this->utilityFuncs->getSingle($currentSettings, 'htmlEmailAsAttachment');
                        if (intval($htmlEmailAsAttachment) === 1) {
                            $emailSettings['htmlEmailAsAttachment'] = 1;
                        }

                        break;
                    case 'deleteGeneratedFiles':
                        $htmlEmailAsAttachment = $this->utilityFuncs->getSingle($currentSettings, 'deleteGeneratedFiles');
                        if (intval($htmlEmailAsAttachment) === 1) {
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
}
