<?php

namespace Typoheads\Formhandler\Ajax;

use TYPO3\CMS\Core\Database\ConnectionPool;
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

use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Utility\Globals;

/**
 * A class calling the controller and returning the form content as JSON. This class is called via AJAX.
 */
class Submit
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var \Typoheads\Formhandler\Component\Manager
     */
    private $componentManager;

    /**
     * Main method of the class.
     *
     * @return string The HTML list of remaining files to be displayed in the form
     */
    public function main()
    {
        $this->init();

        $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.'];
        $settings['usePredef'] = Globals::getSession()->get('predef');

        $content = $GLOBALS['TSFE']->cObj->cObjGetSingle('USER', $settings);

        $content = '{' . json_encode('form') . ':' . json_encode($content, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . '}';
        print $content;
    }

    /**
     * Initialize the class. Read GET parameters
     */
    protected function init()
    {
        $id = (int)($_GET['pid'] ?? $_GET['id'] ?? 0);

        $this->componentManager = GeneralUtility::makeInstance(Manager::class);
        \Typoheads\Formhandler\Utility\GeneralUtility::initializeTSFE($id);

        $elementUID = (int)$_GET['uid'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $row = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($elementUID, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetch();
        if (!empty($row)) {
            $GLOBALS['TSFE']->cObj->data = $row;
            $GLOBALS['TSFE']->cObj->current = 'tt_content_' . $elementUID;
        }

        Globals::setCObj($GLOBALS['TSFE']->cObj);
        $randomID = htmlspecialchars(GeneralUtility::_GP('randomID'));
        Globals::setRandomID($randomID);
        Globals::setAjaxMode(true);
        if (!Globals::getSession()) {
            $ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['Tx_Formhandler.']['settings.'];
            $sessionClass = \Typoheads\Formhandler\Utility\GeneralUtility::getPreparedClassName($ts['session.'], 'Session\PHP');
            Globals::setSession($this->componentManager->getComponent($sessionClass));
        }

        $this->settings = Globals::getSession()->get('settings');

        //init ajax
        if ($this->settings['ajax.']) {
            $class = \Typoheads\Formhandler\Utility\GeneralUtility::getPreparedClassName($this->settings['ajax.'], 'AjaxHandler\JQuery');
            $ajaxHandler = $this->componentManager->getComponent($class);
            Globals::setAjaxHandler($ajaxHandler);

            $ajaxHandler->init($this->settings['ajax.']['config.']);
            $ajaxHandler->initAjax();
        }
    }
}
