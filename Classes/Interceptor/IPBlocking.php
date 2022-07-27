<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Interceptor;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 *
 * @see Tx_Formhandler_Logger_DB
 */
class IPBlocking extends AbstractInterceptor {
  /**
   * The table where the form submissions are logged.
   */
  protected string $logTable = 'tx_formhandler_log';

  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    $ip = (array) ($this->settings['ip.'] ?? []);
    $timebase = (array) ($ip['timebase.'] ?? []);
    $ipTimebaseValue = intval($this->utilityFuncs->getSingle($timebase, 'value'));
    $ipTimebaseUnit = $this->utilityFuncs->getSingle($timebase, 'unit');
    $ipMaxValue = intval($this->utilityFuncs->getSingle($ip, 'threshold'));

    if ($ipTimebaseValue && $ipTimebaseUnit && $ipMaxValue) {
      $this->check($ipTimebaseValue, $ipTimebaseUnit, $ipMaxValue, true);
    }

    $global = (array) ($this->settings['global.'] ?? []);
    $timebase = (array) ($global['timebase.'] ?? []);
    $globalTimebaseValue = intval($this->utilityFuncs->getSingle($timebase, 'value'));
    $globalTimebaseUnit = $this->utilityFuncs->getSingle($timebase, 'unit');
    $globalMaxValue = intval($this->utilityFuncs->getSingle($global, 'threshold'));

    if ($globalTimebaseValue && $globalTimebaseUnit && $globalMaxValue) {
      $this->check($globalTimebaseValue, $globalTimebaseUnit, $globalMaxValue, false);
    }

    return $this->gp;
  }

  /**
   * Checks if the form got submitted too often and throws Exception if true.
   *
   * @param int    $value        Timebase value
   * @param string $unit         Timebase unit (seconds|minutes|hours|days)
   * @param int    $maxValue     maximum amount of submissions in given time base
   * @param bool   $addIPToWhere add IP address to where clause
   */
  private function check(int $value, string $unit, int $maxValue, bool $addIPToWhere = true): void {
    $timestamp = $this->utilityFuncs->getTimestamp($value, $unit);

    /** @var ConnectionPool $connectionPool */
    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    $queryBuilder = $connectionPool->getQueryBuilderForTable($this->logTable);

    $queryBuilder->getRestrictions()->removeAll();
    $queryBuilder
      ->select('uid', 'ip', 'crdate', 'params')
      ->from($this->logTable)
      ->where(
        $queryBuilder->expr()->gte('crdate', $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT))
      )
    ;

    if ($addIPToWhere) {
      $queryBuilder->andWhere(
        $queryBuilder->expr()->eq('ip', $queryBuilder->createNamedParameter(GeneralUtility::getIndpEnv('REMOTE_ADDR')))
      );
    }
    $stmt = $queryBuilder->executeQuery();
    if ($stmt->rowCount() >= $maxValue) {
      $this->log(true);
      $message = 'You are not allowed to send more mails because the form got submitted too many times ';
      if ($addIPToWhere) {
        $message .= 'by your IP address ';
      }
      $message .= 'in the last '.$value.' '.$unit.'!';

      $report = (array) ($this->settings['report.'] ?? []);
      if (isset($report['email'])) {
        $rows = $stmt->fetchAllAssociative();
        $intervalValue = intval($this->utilityFuncs->getSingle((array) ($report['interval.'] ?? []), 'value'));
        $intervalUnit = $this->utilityFuncs->getSingle((array) ($report['interval.'] ?? []), 'unit');
        $send = false;
        if ($intervalUnit && $intervalValue) {
          $intervalTstamp = $this->utilityFuncs->getTimestamp($intervalValue, $intervalUnit);
          $queryBuilder = $connectionPool->getQueryBuilderForTable($this->logTable);
          $queryBuilder->getRestrictions()->removeAll();
          $queryBuilder
            ->count('*')
            ->from($this->logTable)
            ->where(
              $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($GLOBALS['TSFE']->id, \PDO::PARAM_INT)),
              $queryBuilder->expr()->gt('crdate', $queryBuilder->createNamedParameter($intervalTstamp, \PDO::PARAM_INT))
            )
          ;

          if ($addIPToWhere) {
            $queryBuilder->andWhere(
              $queryBuilder->expr()->eq('ip', $queryBuilder->createNamedParameter(GeneralUtility::getIndpEnv('REMOTE_ADDR')))
            );
          }
          if ($queryBuilder->executeQuery()->fetchOne() > 0) {
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
   * @param string                           $type (ip|global) Defines the message sent
   * @param array<int, array<string, mixed>> $rows The select rows of log table
   */
  private function sendReport(string $type, array $rows): void {
    $report = (array) ($this->settings['report.'] ?? []);
    $email = $this->utilityFuncs->getSingle($report, 'email');
    $email = GeneralUtility::trimExplode(',', $email);
    $sender = $this->utilityFuncs->getSingle($report, 'sender');
    $subject = $this->utilityFuncs->getSingle($report, 'subject');

    if ('ip' == $type) {
      $message = 'IP address "'.strval(GeneralUtility::getIndpEnv('REMOTE_ADDR')).'" has submitted a form too many times!';
    } else {
      $message = 'A form got submitted too many times!';
    }

    $message .= "\n\n".'This is the URL to the form: '.strval(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
    if (is_array($rows)) {
      $message .= "\n\n".'These are the submitted values:'."\n\n";
      foreach ($rows as $idx => $row) {
        $message .= date('Y/m/d H:i:s', intval($row['crdate'] ?? 0)).":\n";
        $message .= 'IP: '.$row['ip']."\n";
        $message .= 'Params:'."\n";
        $params = (array) unserialize(strval($row['params'] ?? ''));
        foreach ($params as $key => $value) {
          if (is_array($value)) {
            $value = implode(',', $value);
          }
          $message .= "\t".$key.': '.$value."\n";
        }
        $message .= '---------------------------------------'."\n";
      }
    }

    // init mailer object
    $emailClass = $this->utilityFuncs->getPreparedClassName((array) ($this->settings['mailer.'] ?? []), 'Mailer\HtmlMail');

    /** @var TYPO3Mailer $emailObj */
    $emailObj = GeneralUtility::makeInstance($emailClass);
    $emailObj->init($this->gp, []);

    // set e-mail options
    $emailObj->setSubject($subject);
    $emailObj->setSender($sender, '');
    $emailObj->setPlain($message);

    // send e-mails
    $sent = $emailObj->send($email);
    if ($sent) {
      $this->utilityFuncs->debugMessage('mail_sent', $email);
      $this->utilityFuncs->debugMessage('mail_sender', [$sender]);
      $this->utilityFuncs->debugMessage('mail_subject', [$subject]);
      $this->utilityFuncs->debugMessage('mail_message', [], 1, [$message]);
    } else {
      $this->utilityFuncs->debugMessage('mail_not_sent', $email, 2);
      $this->utilityFuncs->debugMessage('mail_sender', [$sender]);
      $this->utilityFuncs->debugMessage('mail_subject', [$subject]);
      $this->utilityFuncs->debugMessage('mail_message', [], 1, [$message]);
    }
  }
}
