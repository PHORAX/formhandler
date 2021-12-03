<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Mailer;

interface MailerInterface
{

    /**
     * Sends the email to the given reccipients
     *
     * @param array $recipients
     * @return bool Sent successfully?
     */
    public function send(array $recipients): bool;

    /**
     * Set the HTML content of the email
     *
     * @param string $html The HTML content
     */
    public function setHTML(string $html): void;

    /**
     * Set the plain text content of the email
     *
     * @param string $plain The plain text content
     */
    public function setPlain(string $plain): void;

    /**
     * Set the subject of the email
     *
     * @param string $value The subject
     */
    public function setSubject(string $value): void;

    /**
     * Set the sender of the email
     *
     * @param string $email The email address
     * @param string $name The name
     */
    public function setSender(string $email, string $name): void;

    /**
     * Set the reply to of the email
     *
     * @param string $email The email address
     * @param string $name The name
     */
    public function setReplyTo(string $email, string $name): void;

    /**
     * Add a CC recipient of the email
     *
     * @param string $email The email address
     * @param string $name The name
     */
    public function addCc(string $email, string $name): void;

    /**
     * Add a BCC recipient of the email
     *
     * @param string $email The email address
     * @param string $name The name
     */
    public function addBcc(string $email, string $name): void;

    /**
     * Set the return path of the email
     *
     * @param string $value The return path
     */
    public function setReturnPath(string $value): void;

    /**
     * Add an email header
     *
     * @param string $value The header
     */
    public function addHeader(string $value): void;

    /**
     * Add an attachment to the email
     *
     * @param string $value The file name
     */
    public function addAttachment(string $value): void;

    /**
     * Returns the HTML content of the email
     *
     * @return string
     */
    public function getHTML(): string;

    /**
     * Returns the plain text content of the email
     *
     * @return string
     */
    public function getPlain(): string;

    /**
     * Returns the subject of the email
     *
     * @return string
     */
    public function getSubject(): string;

    /**
     * Returns the sender of the email
     *
     * @return string
     */
    public function getSender(): array;

    /**
     * Returns the reply to of the email
     *
     * @return string
     */
    public function getReplyTo(): array;

    /**
     * Returns the CC recipients of the email
     *
     * @return array
     */
    public function getCc(): array;

    /**
     * Returns the BCC recipients of the email
     *
     * @return array
     */
    public function getBcc(): array;

    /**
     * Returns the return path of the email
     *
     * @return string
     */
    public function getReturnPath(): ?\Symfony\Component\Mime\Address;

    /**
     * Embeds an image to the email content
     *
     * @param string $image The image path
     */
    public function embed(string $image): \Symfony\Component\Mime\Email;
}
