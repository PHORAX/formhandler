<?php
namespace Typoheads\Formhandler\Ajax;

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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
	 * @param ServerRequestInterface $request
	 * @param Response|null $response
	 * @return null|Response      * @return string The HTML list of remaining files to be displayed in the form
	 */
    public function main(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $this->init();

        $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.'];
        $settings['usePredef'] = Globals::getSession()->get('predef');

        $content = $GLOBALS['TSFE']->cObj->cObjGetSingle('USER', $settings);

        $content = '{' . json_encode('form') . ':' . json_encode($content, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . '}';
        
        // print $content;
        $response = GeneralUtility::makeInstance(Response::class);
        //$response = $response->withHeader('Content-type', 'text/html');   // I'm not really sure if the output is always of one type
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * Initialize the class. Read GET parameters
     * @param ServerRequestInterface $request
     * @return void
     */
    protected function init()
    {
        if (isset($_GET['pid'])) {
            $id = intval($_GET['pid']);
        } else {
            $id = intval($_GET['id']);
        }

        $this->componentManager = GeneralUtility::makeInstance(\Typoheads\Formhandler\Component\Manager::class);

        $elementUID = intval($_GET['uid']);
        
        /** @var QueryBuilder $queryBuilder */
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->createQueryBuilder();
		$row = $queryBuilder
			->select('*')
			->from('tt_content')
			->where(
				$queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($elementUID, \PDO::PARAM_INT))
			)
			->setMaxResults(1)
			->execute()
			->fetch();
		
        if (!empty($row)) {
            $GLOBALS['TSFE']->cObj->data = $row;
            $GLOBALS['TSFE']->cObj->current = 'tt_content_' . $elementUID;
        }

        Globals::setCObj($GLOBALS['TSFE']->cObj);
        $randomID = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('randomID'));
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
