<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Ajax;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\AjaxHandler\AbstractAjaxHandler;
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
 * A class calling the controller and returning the form content as JSON. This class is called via AJAX.
 *
 * @abstract
 */
class Submit extends AbstractAjax {
  /**
   * Main method of the class.
   */
  public function main(): ResponseInterface {
    // init ajax
    if ($this->settings['ajax.']) {
      $class = $this->utilityFuncs->getPreparedClassName($this->settings['ajax.'], 'AjaxHandler\JQuery');

      /** @var AbstractAjaxHandler $ajaxHandler */
      $ajaxHandler = GeneralUtility::makeInstance($class);
      $this->globals->setAjaxHandler($ajaxHandler);

      $ajaxHandler->init($this->settings['ajax.']['config.']);
      $ajaxHandler->initAjax();
    }

    $settings = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.'];
    $settings['usePredef'] = Globals::getSession()?->get('predef');

    $content = $GLOBALS['TSFE']->cObj->cObjGetSingle('USER', $settings);

    $content = '{'.json_encode('form').':'.json_encode($content, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS).'}';

    return new HtmlResponse($content, 200);
  }
}
