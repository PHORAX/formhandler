<?php
namespace Typoheads\Formhandler\Session;

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
 * A session class for Formhandler using TYPO3 sessions
 */
class TYPO3 extends AbstractSession
{

    /* (non-PHPdoc)
     * @see Classes/Session/Tx_Formhandler_AbstractSession#set()
    */
    public function set($key, $value)
    {
        $data = $GLOBALS['TSFE']->fe_user->getKey('ses', 'formhandler');
        if (!is_array($data[$this->globals->getRandomID()])) {
            $data[$this->globals->getRandomID()] = [];
        }
        $data[$this->globals->getRandomID()][$key] = $value;
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'formhandler', $data);
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }

    /* (non-PHPdoc)
     * @see Classes/Session/Tx_Formhandler_AbstractSession#setMultiple()
    */
    public function setMultiple($values)
    {
        if (is_array($values) && !empty($values)) {
            $data = $GLOBALS['TSFE']->fe_user->getKey('ses', 'formhandler');
            if (!is_array($data[$this->globals->getRandomID()])) {
                $data[$this->globals->getRandomID()] = [];
            }

            foreach ($values as $key => $value) {
                $data[$this->globals->getRandomID()][$key] = $value;
            }

            $GLOBALS['TSFE']->fe_user->setKey('ses', 'formhandler', $data);
            $GLOBALS['TSFE']->fe_user->storeSessionData();
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Session/Tx_Formhandler_AbstractSession#get()
    */
    public function get($key)
    {
        $data = $GLOBALS['TSFE']->fe_user->getKey('ses', 'formhandler');
        if (!is_array($data[$this->globals->getRandomID()])) {
            $data[$this->globals->getRandomID()] = [];
        }
        return $data[$this->globals->getRandomID()][$key];
    }

    /* (non-PHPdoc)
     * @see Classes/Session/Tx_Formhandler_AbstractSession#exists()
    */
    public function exists()
    {
        $data = $GLOBALS['TSFE']->fe_user->getKey('ses', 'formhandler');
        return is_array($data[$this->globals->getRandomID()]);
    }

    /* (non-PHPdoc)
     * @see Classes/Session/Tx_Formhandler_AbstractSession#reset()
    */
    public function reset()
    {
        $data = $GLOBALS['TSFE']->fe_user->getKey('ses', 'formhandler');
        unset($data[$this->globals->getRandomID()]);
        $GLOBALS['TSFE']->fe_user->setKey('ses', 'formhandler', $data);
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }

    public function init($gp, $settings)
    {
        parent::init($gp, $settings);

        $threshold = $this->getOldSessionThreshold();
        $data = $GLOBALS['TSFE']->fe_user->getKey('ses', 'formhandler');
        if (is_array($data)) {
            foreach ($data as $hashedID => $sesData) {
                if (!$this->gp['submitted'] && $this->globals->getFormValuesPrefix() === $sesData['formValuesPrefix'] && $sesData['creationTstamp'] < $threshold) {
                    unset($data[$hashedID]);
                }
            }
        } else {
            $data = [];
        }

        $GLOBALS['TSFE']->fe_user->setKey('ses', 'formhandler', $data);
        $GLOBALS['TSFE']->fe_user->storeSessionData();
    }
}
