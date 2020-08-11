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
*
*                                                                        */

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @todo later: 10.4 untested! (I didn't find use case in my projects) 
 * A class validating a field via AJAX.
 */
class Validate
{

    /**
     * @var array
     */
    protected $templates = array(
        'spanSuccess' => '<span class="success">%s</span>',
        'spanError' => '<span class="error">%s</span>',
    );
    
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
        $field = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('field'));
        if ($field) {
            $randomID = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('randomID'));
            \Typoheads\Formhandler\Utility\Globals::setCObj($GLOBALS['TSFE']->cObj);
            \Typoheads\Formhandler\Utility\Globals::setRandomID($randomID);
            if (!\Typoheads\Formhandler\Utility\Globals::getSession()) {
                $ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['Tx_Formhandler.']['settings.'];
                $sessionClass = \Typoheads\Formhandler\Utility\GeneralUtility::getPreparedClassName($ts['session.'], 'Session\PHP');
                \Typoheads\Formhandler\Utility\Globals::setSession($this->componentManager->getComponent($sessionClass));
            }
            $this->settings = \Typoheads\Formhandler\Utility\Globals::getSession()->get('settings');
            \Typoheads\Formhandler\Utility\Globals::setFormValuesPrefix(\Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings, 'formValuesPrefix'));
            $gp = \Typoheads\Formhandler\Utility\GeneralUtility::getMergedGP();
            $validator = $this->componentManager->getComponent('\Typoheads\Formhandler\Validator\Ajax');
            $errors = [];
            $valid = $validator->validateAjax($field, $gp, $errors);

            if ($valid) {
                $content = \Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings['ajax.']['config.'], 'ok');
                if (strlen($content) === 0) {
                    $content = '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Resources/Public/Images/ok.png' . '" />';
                } else {
                    $gp = [
                        $_GET['field'] => $_GET['value']
                    ];
                    $view = $this->initView($content);
                    $content = $view->render($gp, $errors);
                }
                $content = sprintf($this->templates['spanSuccess'], $content);
            } else {
                $content = \Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings['ajax.']['config.'], 'notOk');
                if (strlen($content) === 0) {
                    $content = '<img src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Resources/Public/Images/notok.png' . '" />';
                } else {
                    $view = $this->initView($content);
                    $gp = [
                        $_GET['field'] => $_GET['value']
                    ];
                    $content = $view->render($gp, $errors);
                }
                $content = sprintf($this->templates['spanError'], $content);
            }
            // print $content;
	        $response = GeneralUtility::makeInstance(Response::class);
	        $response->getBody()->write($content);
	        return $response;
        }
    }

    /**
     * Initialize the class. Read GET parameters
     *
     * @return void
     */
    protected function init()
    {
        if (isset($_GET['pid'])) {
            $this->id = intval($_GET['pid']);
        } else {
            $this->id = intval($_GET['id']);
        }
        $this->componentManager = GeneralUtility::makeInstance(\Typoheads\Formhandler\Component\Manager::class);
        \Typoheads\Formhandler\Utility\Globals::setAjaxMode(true);
    }

    /**
     * Initialize the AJAX validation view.
     *
     * @param string $content The raw content
     * @return \Typoheads\Formhandler\View\AjaxValidation The view class
     */
    protected function initView($content)
    {
    	$settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.'];
        $viewClass = '\Typoheads\Formhandler\View\AjaxValidation';
        $view = $this->componentManager->getComponent($viewClass);
        $view->setLangFiles(\Typoheads\Formhandler\Utility\GeneralUtility::readLanguageFiles([], $settings));
        $view->setSettings($settings);
        $templateName = 'AJAX';
        $template = str_replace('###fieldname###', htmlspecialchars($_GET['field']), $content);
        $template = '###TEMPLATE_' . $templateName . '###' . $template . '###TEMPLATE_' . $templateName . '###';
        $view->setTemplate($template, 'AJAX');
        return $view;
    }
}
