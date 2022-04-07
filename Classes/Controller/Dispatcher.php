<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Utility\Globals;

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
 * The Dispatcher instantiates the Component Manager and delegates the process to the given controller.
 */
class Dispatcher extends AbstractPlugin {
  /**
   * Compontent Manager.
   */
  protected Manager $componentManager;

  /**
   * The global Formhandler values.
   */
  protected Globals $globals;

  /**
   * The Formhandler utility functions.
   */
  protected \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs;

  /**
   * Main method of the dispatcher. This method is called as a user function.
   *
   * @param array $setup The TypoScript config
   *
   * @return string rendered view
   */
  public function main(?string $content, array $setup): string {
    $this->componentManager = GeneralUtility::makeInstance(Manager::class);
    $this->globals = GeneralUtility::makeInstance(Globals::class);
    $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);

    try {
      // init flexform
      $this->pi_initPIflexForm();

      /*
       * Parse values from flexform:
       * - Template file
       * - Translation file
       * - Predefined form
       * - E-mail settings
       * - Required fields
       * - Redirect page
       */
      $templateFile = isset($this->cObj->data['pi_flexform']['data']['sDEF']['lDEF']['template_file']) ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'sDEF') : '';
      $langFile = isset($this->cObj->data['pi_flexform']['data']['sDEF']['lDEF']['lang_file']) ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'lang_file', 'sDEF') : '';
      $predef = isset($this->cObj->data['pi_flexform']['data']['sDEF']['lDEF']['predefined']) ? $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'predefined', 'sDEF') : '';

      $this->globals->setCObj($this->cObj);
      $this->globals->getCObj()->setCurrentVal($predef);
      if (isset($setup['usePredef'])) {
        $predef = $this->utilityFuncs->getSingle($setup, 'usePredef');
      }

      $this->globals->setPredef($predef);
      $this->globals->setOverrideSettings($setup);

      /*
       * set controller:
       * 1. Default controller
       * 2. TypoScript
       */
      $controllerClass = '\Typoheads\Formhandler\Controller\Form';
      if (isset($setup['controller'])) {
        $controllerClass = $setup['controller'];
      }

      /** @var \Typoheads\Formhandler\Controller\Form $controller */
      $controller = $this->componentManager->getComponent($controllerClass);

      if (isset($content)) {
        $controller->setContent($this->componentManager->getComponent($this->utilityFuncs->prepareClassName('Typoheads\Formhandler\Controller\Content'), $content));
      }
      if (strlen($templateFile) > 0) {
        $controller->setTemplateFile($templateFile);
      }
      if (strlen($langFile) > 0) {
        $controller->setLangFiles([$langFile]);
      }
      if (strlen($predef) > 0) {
        $controller->setPredefined($predef);
      }

      $result = $controller->process();
    } catch (\Exception $e) {
      $settings = $this->globals->getSettings();
      if (isset($settings) && is_array($settings) && isset($settings['debug']) && (bool) $settings['debug']) {
        DebuggerUtility::var_dump($e);
      }
      GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->error(
        $e->getFile().'('.$e->getLine().')'.' '.$e->getMessage(),
        ['formhandler']
      );

      $result = $this->utilityFuncs->getTranslatedMessage($this->globals->getLangFiles(), 'fe-exception');
      if (!$result) {
        $result = '<div style="color:red; font-weight: bold">'.$this->utilityFuncs->getExceptionMessage('fe-exception').'</div>';
      }
      if (null != $this->globals->getSession() && (bool) $this->globals->getSession()->get('debug')) {
        $result = '<div style="color:red; font-weight: bold">'.$e->getMessage().'</div>';
        $result .= '<div style="color:red; font-weight: bold">File: '.$e->getFile().'('.$e->getLine().')</div>';
        $result .= '<div style="color:red; font-weight: bold">'.$e->getTraceAsString().'</div>';
      }
    }
    if (null != $this->globals->getSession() && (bool) $this->globals->getSession()->get('debug')) {
      $debuggers = $this->globals->getDebuggers();
      foreach ($debuggers as $idx => $debugger) {
        $debugger->outputDebugLog();
      }
    }

    return $result;
  }
}
