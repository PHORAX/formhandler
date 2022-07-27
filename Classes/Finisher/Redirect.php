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
 * Sample implementation of a Finisher Class used by Formhandler redirecting to another page.
 * This class needs a parameter "redirect_page" to be set in TS.
 *
 * Sample configuration:
 *
 * <code>
 * finishers.4.class = Finisher_Redirect
 * finishers.4.config.redirectPage = 65
 * </code>
 */
class Redirect extends AbstractFinisher {
  /**
   * Method to set GET/POST for this class and load the configuration.
   */
  public function init(array $gp, array $tsConfig): void {
    $this->gp = $gp;
    $this->settings = $tsConfig;
    $redirect = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], 'redirect_page', 'sMISC');
    if ($redirect) {
      $this->settings['redirectPage'] = $redirect;
    }
  }

  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    // read redirect page
    $redirectPage = $this->utilityFuncs->getSingle($this->settings, 'redirectPage');
    if (empty($redirectPage)) {
      return $this->gp;
    }
    $this->globals->getSession()?->reset();

    $this->utilityFuncs->doRedirectBasedOnSettings($this->settings, $this->gp);

    return [];
  }
}
