<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Mailer;

use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Controller\Configuration;
use Typoheads\Formhandler\Utility\GeneralUtility;
use Typoheads\Formhandler\Utility\Globals;

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

class TYPO3Mailer extends AbstractMailer implements MailerInterface
{

    /**
     * The TYPO3 mail message object
     *
     * @var \TYPO3\CMS\Core\Mail\MailMessage
     */
    protected \TYPO3\CMS\Core\Mail\MailMessage $emailObj;


    /**
     * Initializes the email object and calls the parent constructor
     *
     * @param \Typoheads\Formhandler\Component\Manager $componentManager
     * @param \Typoheads\Formhandler\Controller\Configuration $configuration
     * @param \Typoheads\Formhandler\Utility\Globals $globals
     * @param \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs
     */
    public function __construct(
        Manager $componentManager,
        Configuration $configuration,
        Globals $globals,
        GeneralUtility $utilityFuncs
    ) {
        parent::__construct($componentManager, $configuration, $globals, $utilityFuncs);
        $this->emailObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Mail\MailMessage');
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_Formhandler_MailerInterface#send()
    */
    public function send(array $recipients): bool
    {
        if (!empty($recipients)) {
            $this->emailObj->setTo($recipients);

            $numberOfEmailsSent = $this->emailObj->send();

            if ($numberOfEmailsSent) {
                return true;
            }
        }

        return false;
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setHTML()
    */
    public function setHTML(string $html): void
    {
        $this->emailObj->html($html);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setPlain()
    */
    public function setPlain(string $plain): void
    {
        $this->emailObj->text($plain);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setSubject()
    */
    public function setSubject(string $value): void
    {
        $this->emailObj->setSubject($value);
    }

    /**
     * Sets the name and email of the "From" header.
     *
     * The function name setSender is misleading since there is
     * also a "Sender" header which is not set by this method
     *
     * @param string $email
     * @param string $name
     */
    public function setSender(string $email, string $name): void
    {
        if (!empty($email)) {
            $this->emailObj->setFrom($email, $name);
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setReplyTo()
    */
    public function setReplyTo(string $email, string $name): void
    {
        if (!empty($email)) {
            $this->emailObj->setReplyTo($email, $name);
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addCc()
    */
    public function addCc(string $email, string $name): void
    {
        $this->emailObj->addCc($email, $name);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addBcc()
    */
    public function addBcc(string $email, string $name): void
    {
        $this->emailObj->addBcc($email, $name);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setReturnPath()
    */
    public function setReturnPath(string $value): void
    {
        $this->emailObj->setReturnPath($value);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addHeader()
    */
    public function addHeader(string $value): void
    {
        //@TODO: Find a good way to make headers configurable
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addAttachment()
    */
    public function addAttachment(string $value): void
    {
        $this->emailObj->attachFromPath($value);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getHTML()
    */
    public function getHTML(): string
    {
        return $this->emailObj->getHtmlBody() ?? '';
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getPlain()
    */
    public function getPlain(): string
    {
        return $this->emailObj->getTextBody() ?? '';
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getSubject()
    */
    public function getSubject(): string
    {
        return $this->emailObj->getSubject();
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getSender()
    */
    public function getSender(): array
    {
        return $this->emailObj->getFrom();
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getReplyTo()
    */
    public function getReplyTo(): array
    {
        return $this->emailObj->getReplyTo();
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getCc()
    */
    public function getCc(): array
    {
        $ccArray = $this->emailObj->getCc();
        $ccConcat = [];
        if (is_array($ccArray)) {
            foreach ($ccArray as $email => $name) {
                $ccConcat[] = $name . ' <' . $email . '>';
            }
        }
        return $ccConcat;
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getBcc()
    */
    public function getBcc(): array
    {
        $bccArray = $this->emailObj->getBcc();
        $bccConcat = [];
        if (is_array($bccArray)) {
            foreach ($bccArray as $email => $name) {
                $bccConcat[] = $name . ' <' . $email . '>';
            }
        }
        return $bccConcat;
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getReturnPath()
    */
    public function getReturnPath(): ?\Symfony\Component\Mime\Address
    {
        return $this->emailObj->getReturnPath();
    }

    public function embed(string $image): \Symfony\Component\Mime\Email
    {
        return $this->emailObj->embedFromPath($image);
    }
}
