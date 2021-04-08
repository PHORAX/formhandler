<?php

namespace Typoheads\Formhandler\Mailer;

use TYPO3\CMS\Core\Mail\MailMessage;
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
    protected $emailObj;

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
        $this->emailObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MailMessage::class);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_Formhandler_MailerInterface#send()
    */
    public function send($recipients)
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
    public function setHTML($html)
    {
        if (!empty($html)) {
            $this->emailObj->html($html);
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setPlain()
    */
    public function setPlain($plain)
    {
        if (!empty($plain)) {
            $this->emailObj->text($plain);
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setSubject()
    */
    public function setSubject($value)
    {
        return $this->emailObj->setSubject((string)$value);
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
    public function setSender($email, $name)
    {
        if (!empty($email)) {
            return $this->emailObj->setFrom($email, $name);
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setReplyTo()
    */
    public function setReplyTo($email, $name)
    {
        if (!empty($email)) {
            $this->emailObj->setReplyTo($email, $name);
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addCc()
    */
    public function addCc($email, $name)
    {
        $this->emailObj->addCc($email, $name);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addBcc()
    */
    public function addBcc($email, $name)
    {
        $this->emailObj->addBcc($email, $name);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#setReturnPath()
    */
    public function setReturnPath($value)
    {
        $this->emailObj->setReturnPath($value);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addHeader()
    */
    public function addHeader($value)
    {
        //@TODO: Find a good way to make headers configurable
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#addAttachment()
    */
    public function addAttachment($value)
    {
        $this->emailObj->attachFromPath($value);
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getHTML()
    */
    public function getHTML()
    {
        return $this->emailObj->getHtmlBody() ?? '';
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getPlain()
    */
    public function getPlain()
    {
        return $this->emailObj->getTextBody() ?? '';
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getSubject()
    */
    public function getSubject()
    {
        return $this->emailObj->getSubject();
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getSender()
    */
    public function getSender()
    {
        return $this->emailObj->getFrom();
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getReplyTo()
    */
    public function getReplyTo()
    {
        return $this->emailObj->getReplyTo();
    }

    /* (non-PHPdoc)
     * @see Classes/Mailer/Tx_FormhandlerMailerInterface#getCc()
    */
    public function getCc()
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
    public function getBcc()
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
    public function getReturnPath()
    {
        return $this->emailObj->getReturnPath();
    }

    public function embed($image)
    {
        return $this->emailObj->embedFromPath($image);
    }
}
