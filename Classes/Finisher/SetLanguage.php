<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

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
 * Finisher to set the currently used language to a set value.
 * Useful if you want to send the admin email in a specific language and do not want to use the language of the user.
 */
class SetLanguage extends AbstractFinisher {
  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    if (null === $this->globals->getSession()?->get('originalLanguage')) {
      $this->globals->getSession()?->set('originalLanguage', $GLOBALS['TSFE']->lang);
    }
    $languageCode = $this->utilityFuncs->getSingle($this->settings, 'languageCode');
    if ($languageCode) {
      $lang = strtolower($languageCode);
      $GLOBALS['TSFE']->config['config']['language'] = $lang;
      $GLOBALS['TSFE']->initLLvars();
      $this->utilityFuncs->debugMessage('Language set to "'.$lang.'"!', [], 1);
    } else {
      $this->utilityFuncs->debugMessage('Unable to set language! Language code set in TypoScript is empty!', [], 2);
    }

    return $this->gp;
  }

  /**
   * Method to define whether the config is valid or not. If no, display a warning on the frontend.
   * The default value is true. This up to the finisher to overload this method.
   */
  public function validateConfig(): bool {
    $found = true;
    $settings = $this->globals->getSettings();
    if (isset($settings['finishers.']) && is_array($settings['finishers.'])) {
      $found = false;
      foreach ($settings['finishers.'] as $finisherConfig) {
        $currentFinisherClass = $this->utilityFuncs->getPreparedClassName($finisherConfig);
        if ($currentFinisherClass === $this->utilityFuncs->prepareClassName('\Typoheads\Formhandler\Finisher\RestoreLanguage')) {
          $found = true;
        }
      }
      if (!$found) {
        $this->utilityFuncs->throwException('No Finisher_RestoreLanguage found in the TypoScript setup! You have to reset the language to the original value after you changed it using Finisher_SetLanguage');
      }
    }

    return $found;
  }
}
