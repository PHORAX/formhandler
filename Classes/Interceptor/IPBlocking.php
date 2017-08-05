<?php
namespace Typoheads\Formhandler\Interceptor;

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
 * An interceptor checking if form got submitted too often by an IP address or globally.
 * Settings how often a form is allowed to be submitted and the period of time are set in TypoScript.
 *
 * This interceptor uses log entries made by Tx_Formhandler_Logger_DB.
 *
 * Example:
 * <code>
 * saveInterceptors.1.class = Tx_Formhandler_Interceptor_IPBlocking
 *
 * saveInterceptors.1.config.redirectPage = 17
 *
 * saveInterceptors.1.config.report.email = example@host.com,example2@host.com
 * saveInterceptors.1.config.report.subject = Submission limit reached
 * saveInterceptors.1.config.report.sender = somebody@otherhost.com
 * saveInterceptors.1.config.report.interval.value = 5
 * saveInterceptors.1.config.report.interval.unit = minutes
 *
 * saveInterceptors.1.config.ip.timebase.value = 5
 * saveInterceptors.1.config.ip.timebase.unit = minutes
 * saveInterceptors.1.config.ip.threshold = 2
 *
 * saveInterceptors.1.config.global.timebase.value = 5
 * saveInterceptors.1.config.global.timebase.unit = minutes
 * saveInterceptors.1.config.global.threshold = 30
 * </code>
 *
 * This example configuration says that the form is allowed to be submitted twice in a period of 5 minutes and 30 times in 5 minutes globally.
 * @see Tx_Formhandler_Logger_DB
 */
class IPBlocking extends AbstractInterceptor
{

    /**
     * The table where the form submissions are logged
     *
     * @access protected
     * @var string
     */
    protected $logTable = 'tx_formhandler_log';

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        $ipTimebaseValue = $this->utilityFuncs->getSingle($this->settings['ip.']['timebase.'], 'value');
        $ipTimebaseUnit = $this->utilityFuncs->getSingle($this->settings['ip.']['timebase.'], 'unit');
        $ipMaxValue = $this->utilityFuncs->getSingle($this->settings['ip.'], 'threshold');

        if ($ipTimebaseValue && $ipTimebaseUnit && $ipMaxValue) {
            $this->check($ipTimebaseValue, $ipTimebaseUnit, $ipMaxValue, true);
        }

        $globalTimebaseValue = $this->utilityFuncs->getSingle($this->settings['global.']['timebase.'], 'value');
        $globalTimebaseUnit = $this->utilityFuncs->getSingle($this->settings['global.']['timebase.'], 'unit');
        $globalMaxValue = $this->utilityFuncs->getSingle($this->settings['global.'], 'threshold');

        if ($globalTimebaseValue && $globalTimebaseUnit && $globalMaxValue) {
            $this->check($globalTimebaseValue, $globalTimebaseUnit, $globalMaxValue, false);
        }

        return $this->gp;
    }

    /**
     * Checks if the form got submitted too often and throws Exception if true.
     *
     * @param int Timebase value
     * @param string Timebase unit (seconds|minutes|hours|days)
     * @param int maximum amount of submissions in given time base.
     * @param boolean add IP address to where clause
     * @return void
     */
    private function check($value, $unit, $maxValue, $addIPToWhere = true)
    {
        $timestamp = $this->utilityFuncs->getTimestamp($value, $unit);
        $where = 'crdate >= ' . intval($timestamp);
        if ($addIPToWhere) {
            $where = 'ip=\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\' AND ' . $where;
        }
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,ip,crdate,params', $this->logTable, $where);
        if ($res && $GLOBALS['TYPO3_DB']->sql_num_rows($res) >= $maxValue) {
            $this->log(true);
            $message = 'You are not allowed to send more mails because the form got submitted too many times ';
            if ($addIPToWhere) {
                $message .= 'by your IP address ';
            }
            $message .= 'in the last ' . $value . ' ' . $unit . '!';
            if ($this->settings['report.']['email']) {
                while (false !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
                    $rows[] = $row;
                }
                $intervalValue = $this->utilityFuncs->getSingle($this->settings['report.']['interval.'], 'value');
                $intervalUnit = $this->utilityFuncs->getSingle($this->settings['report.']['interval.'], 'unit');
                $send = false;
                if ($intervalUnit && $intervalValue) {
                    $intervalTstamp = $this->utilityFuncs->getTimestamp($intervalValue, $intervalUnit);
                    $where = 'pid=' . $GLOBALS['TSFE']->id . ' AND crdate>' . intval($intervalTstamp);
                    if ($addIPToWhere) {
                        $where .= ' AND ip=\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\'';
                    }

                    $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $this->logTable, $where);
                    if ($count > 0) {
                        $send = true;
                    }
                } else {
                    $send = true;
                }
                if ($send) {
                    if ($addIPToWhere) {
                        $this->sendReport('ip', $rows);
                    } else {
                        $this->sendReport('global', $rows);
                    }
                } else {
                    $this->utilityFuncs->debugMessage('alert_mail_not_sent', [], 2);
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            if ($this->settings['redirectPage']) {
                $this->utilityFuncs->doRedirectBasedOnSettings($this->settings, $this->gp);
            } else {
                throw new \Exception($message);
            }
        }
    }

    /**
     * Sends a report mail to recipients set in TypoScript.
     *
     * @param string (ip|global) Defines the message sent
     * @param array The select rows of log table
     * @return void
     */
    private function sendReport($type, $rows)
    {
        $email = $this->utilityFuncs->getSingle($this->settings['report.'], 'email');
        $email = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $email);
        $sender = $this->utilityFuncs->getSingle($this->settings['report.'], 'sender');
        $subject = $this->utilityFuncs->getSingle($this->settings['report.'], 'subject');

        if ($type == 'ip') {
            $message = 'IP address "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR') . '" has submitted a form too many times!';
        } else {
            $message = 'A form got submitted too many times!';
        }

        $message .= "\n\n" . 'This is the URL to the form: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        if (is_array($rows)) {
            $message .= "\n\n" . 'These are the submitted values:' . "\n\n";
            foreach ($rows as $idx => $row) {
                $message .= date('Y/m/d H:i:s', $row['crdate']) . ":\n";
                $message .= 'IP: ' . $row['ip'] . "\n";
                $message .= 'Params:' . "\n";
                $params = unserialize($row['params']);
                foreach ($params as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $message .= "\t" . $key . ': ' . $value . "\n";
                }
                $message .= '---------------------------------------' . "\n";
            }
        }

        //init mailer object
        $emailClass = $this->utilityFuncs->getPreparedClassName($this->settings['mailer.'], 'Mailer\HtmlMail');
        $emailObj = $this->componentManager->getComponent($emailClass);
        $emailObj->init($this->gp, []);

        //set e-mail options
        $emailObj->setSubject($subject);
        $emailObj->setSender($sender, '');
        $emailObj->setPlain($message);

        //send e-mails
        $sent = $emailObj->send($email);
        if ($sent) {
            $this->utilityFuncs->debugMessage('mail_sent', [$mailto]);
            $this->utilityFuncs->debugMessage('mail_sender', [$emailObj->from_email]);
            $this->utilityFuncs->debugMessage('mail_subject', [$emailObj->subject]);
            $this->utilityFuncs->debugMessage('mail_message', [], 1, [$message]);
        } else {
            $this->utilityFuncs->debugMessage('mail_not_sent', [$mailto], 2);
            $this->utilityFuncs->debugMessage('mail_sender', [$emailObj->from_email]);
            $this->utilityFuncs->debugMessage('mail_subject', [$emailObj->subject]);
            $this->utilityFuncs->debugMessage('mail_message', [], 1, [$message]);
        }
    }
}
