<?php
namespace Typoheads\Formhandler\Mailer;

interface MailerInterface
{

    /**
     * Sends the email to the given reccipients
     *
     * @param array $recipients
     * @return boolean Sent successfully?
     */
    public function send($recipients);

    /**
     * Set the HTML content of the email
     *
     * @param string $html The HTML content
     * @return void
     */
    public function setHTML($html);

    /**
     * Set the plain text content of the email
     *
     * @param string $plain The plain text content
     * @return void
     */
    public function setPlain($plain);

    /**
     * Set the subject of the email
     *
     * @param string $value The subject
     * @return void
     */
    public function setSubject($value);

    /**
     * Set the sender of the email
     *
     * @param string $email The email address
     * @param string $name The name
     * @return void
     */
    public function setSender($email, $name);

    /**
     * Set the reply to of the email
     *
     * @param string $email The email address
     * @param string $name The name
     * @return void
     */
    public function setReplyTo($email, $name);

    /**
     * Add a CC recipient of the email
     *
     * @param string $email The email address
     * @param string $name The name
     * @return void
     */
    public function addCc($email, $name);

    /**
     * Add a BCC recipient of the email
     *
     * @param string $email The email address
     * @param string $name The name
     * @return void
     */
    public function addBcc($email, $name);

    /**
     * Set the return path of the email
     *
     * @param string $value The return path
     * @return void
     */
    public function setReturnPath($value);

    /**
     * Add an email header
     *
     * @param string $value The header
     * @return void
     */
    public function addHeader($value);

    /**
     * Add an attachment to the email
     *
     * @param string $value The file name
     * @return void
     */
    public function addAttachment($value);

    /**
     * Returns the HTML content of the email
     *
     * @return string
     */
    public function getHTML();

    /**
     * Returns the plain text content of the email
     *
     * @return string
     */
    public function getPlain();

    /**
     * Returns the subject of the email
     *
     * @return string
     */
    public function getSubject();

    /**
     * Returns the sender of the email
     *
     * @return string
     */
    public function getSender();

    /**
     * Returns the reply to of the email
     *
     * @return string
     */
    public function getReplyTo();

    /**
     * Returns the CC recipients of the email
     *
     * @return array
     */
    public function getCc();

    /**
     * Returns the BCC recipients of the email
     *
     * @return array
     */
    public function getBcc();

    /**
     * Returns the return path of the email
     *
     * @return string
     */
    public function getReturnPath();

    /**
     * Embeds an image to the email content
     *
     * @param string $image The image path
     * @return void
     */
    public function embed($image);
}
