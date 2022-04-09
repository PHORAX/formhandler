<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Mailer;

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
interface MailerInterface {
  /**
   * Add an attachment to the email.
   *
   * @param string $value The file name
   */
  public function addAttachment(string $value): void;

  /**
   * Add a BCC recipient of the email.
   *
   * @param string $email The email address
   * @param string $name  The name
   */
  public function addBcc(string $email, string $name): void;

  /**
   * Add a CC recipient of the email.
   *
   * @param string $email The email address
   * @param string $name  The name
   */
  public function addCc(string $email, string $name): void;

  /**
   * Add an email header.
   *
   * @param string $value The header
   */
  public function addHeader(string $value): void;

  /**
   * Embeds an image to the email content.
   *
   * @param string $image The image path
   */
  public function embed(string $image): \Symfony\Component\Mime\Email;

  /**
   * Returns the BCC recipients of the email.
   */
  public function getBcc(): array;

  /**
   * Returns the CC recipients of the email.
   */
  public function getCc(): array;

  /**
   * Returns the HTML content of the email.
   */
  public function getHTML(): string;

  /**
   * Returns the plain text content of the email.
   */
  public function getPlain(): string;

  /**
   * Returns the reply to of the email.
   */
  public function getReplyTo(): array;

  /**
   * Returns the return path of the email.
   */
  public function getReturnPath(): ?\Symfony\Component\Mime\Address;

  /**
   * Returns the sender of the email.
   */
  public function getSender(): array;

  /**
   * Returns the subject of the email.
   */
  public function getSubject(): string;

  /**
   * Sends the email to the given reccipients.
   *
   * @return bool Sent successfully?
   */
  public function send(array $recipients): bool;

  /**
   * Set the HTML content of the email.
   */
  public function setHTML(string $html): void;

  /**
   * Set the plain text content of the email.
   */
  public function setPlain(string $plain): void;

  /**
   * Set the reply to of the email.
   *
   * @param string $email The email address
   * @param string $name  The name
   */
  public function setReplyTo(string $email, string $name): void;

  /**
   * Set the return path of the email.
   *
   * @param string $value The return path
   */
  public function setReturnPath(string $value): void;

  /**
   * Set the sender of the email.
   *
   * @param string $email The email address
   * @param string $name  The name
   */
  public function setSender(string $email, string $name): void;

  /**
   * Set the subject of the email.
   *
   * @param string $value The subject
   */
  public function setSubject(string $value): void;
}
