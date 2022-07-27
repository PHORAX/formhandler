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
 * Finisher to restore the currently used language to the original one.
 * Only useful if the language got set using Finisher_SetLanguage before.
 */
class RestoreLanguage extends AbstractFinisher {
  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    $lang = $this->globals->getSession()?->get('originalLanguage');
    if (null !== $lang) {
      $GLOBALS['TSFE']->config['config']['language'] = $lang;
      $GLOBALS['TSFE']->initLLvars();
      $this->globals->getSession()?->set('originalLanguage', null);
      $this->utilityFuncs->debugMessage('Language restored to "'.$lang.'"!', [], 1);
    } else {
      $this->utilityFuncs->debugMessage('Unable to restore language! No original language found!', [], 2);
    }

    return $this->gp;
  }
}
