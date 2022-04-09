<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Utility\Globals;
use Typoheads\Formhandler\View\AjaxValidation;

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
 * A class validating a field via AJAX.
 */
class Validate {
  protected array $templates = [
    'spanSuccess' => '<span class="success">%s</span>',
    'spanError' => '<span class="error">%s</span>',
  ];

  private Manager $componentManager;

  private array $settings = [];

  /**
   * Main method of the class.
   */
  public function main(ServerRequestInterface $request): ResponseInterface {
    $content = '';
    $this->init($request);
    $field = htmlspecialchars(GeneralUtility::_GP('field'));
    if ($field) {
      $randomID = htmlspecialchars(GeneralUtility::_GP('randomID'));
      Globals::setCObj($GLOBALS['TSFE']->cObj);
      Globals::setRandomID($randomID);
      if (null == Globals::getSession()) {
        $ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.']['settings.'];
        $sessionClass = \Typoheads\Formhandler\Utility\GeneralUtility::getPreparedClassName(isset($ts['session.']) ? $ts['session.'] : null, 'Session\PHP');
        Globals::setSession($this->componentManager->getComponent($sessionClass));
      }
      $this->settings = (array) Globals::getSession()->get('settings');
      Globals::setFormValuesPrefix(\Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings, 'formValuesPrefix'));
      $gp = \Typoheads\Formhandler\Utility\GeneralUtility::getMergedGP();
      $validator = $this->componentManager->getComponent('\Typoheads\Formhandler\Validator\Ajax');
      $errors = [];
      $valid = $validator->validateAjax($field, $gp, $errors);

      if ($valid) {
        $content = \Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings['ajax.']['config.'], 'ok');
        if (0 === strlen($content)) {
          $content = '<img src="'.PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('formhandler')).'Resources/Public/Images/ok.png'.'" />';
        } else {
          $gp[$_GET['field']] = $_GET['value'] ?? '';
          $view = $this->initView($content);
          $content = $view->render($gp, $errors);
        }
        $content = sprintf($this->templates['spanSuccess'], $content);
      } else {
        $content = \Typoheads\Formhandler\Utility\GeneralUtility::getSingle($this->settings['ajax.']['config.'], 'notOk');
        if (0 === strlen($content)) {
          $content = '<img src="'.PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('formhandler')).'Resources/Public/Images/notok.png'.'" />';
        } else {
          $view = $this->initView($content);
          $gp[$_GET['field']] = $_GET['value'] ?? '';
          $content = $view->render($gp, $errors);
        }
        $content = sprintf($this->templates['spanError'], $content);
      }
    }

    return new HtmlResponse($content, 200);
  }

  /**
   * Initialize the class. Read GET parameters.
   */
  protected function init(ServerRequestInterface $request): void {
    $GLOBALS['TYPO3_REQUEST'] = $request;

    $this->componentManager = GeneralUtility::makeInstance(Manager::class);
    Globals::setAjaxMode(true);
    \Typoheads\Formhandler\Utility\GeneralUtility::initializeTSFE($request);
  }

  /**
   * Initialize the AJAX validation view.
   *
   * @param string $content The raw content
   *
   * @return AjaxValidation The view class
   */
  protected function initView(string $content): AjaxValidation {
    $viewClass = '\Typoheads\Formhandler\View\AjaxValidation';
    $view = $this->componentManager->getComponent($viewClass);
    $view->setLangFiles(\Typoheads\Formhandler\Utility\GeneralUtility::readLanguageFiles([], $this->settings));
    $view->setSettings($this->settings);
    $templateName = 'AJAX';
    $template = str_replace('###fieldname###', htmlspecialchars($_GET['field']), $content);
    $template = '###TEMPLATE_'.$templateName.'###'.$template.'###TEMPLATE_'.$templateName.'###';
    $view->setTemplate($template, 'AJAX');

    return $view;
  }
}
