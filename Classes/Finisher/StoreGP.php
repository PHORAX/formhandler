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
 * This finisher stores GP to session for further use in other plugins and update session
 * to not loose changes in gp made by other finishers (e.g. insert_id from Finisher_DB)
 * Automaically called if plugin.tx_formhandler_pi1.settings.predef.example.storeGP = 1 is set
 * No further configuration.
 *
 * @author Johannes Feustel
 */
class StoreGP extends AbstractFinisher {
  /**
   * The main method called by the controller.
   */
  public function process(): array|string {
    // store in Session for further use by other plugins
    $this->storeUserGPinSession();

    // update values in session
    $this->updateSession();

    return $this->gp;
  }

  /**
   * Stores the GP in session.
   */
  protected function storeUserGPinSession(): void {
    $sessionKey = 'finisher-storegp';
    if ($this->settings['sessionKey']) {
      $sessionKey = $this->utilityFuncs->getSingle($this->settings, 'sessionKey');
    }
    $dataToStoreInSession = $this->gp;
    $GLOBALS['TSFE']->fe_user->setKey('ses', $sessionKey, $dataToStoreInSession);
    $GLOBALS['TSFE']->fe_user->storeSessionData();
  }

  /**
   * Stores $this->gp parameters in SESSION
   * actually only needed for finisher_submittedok.
   */
  protected function updateSession(): void {
    $newValues = [];

    // set the variables in session
    // no need to seperate steps in finishers, so simply store to step 1
    foreach ($this->gp as $key => $value) {
      $newValues[1][$key] = $value;
    }
    $this->globals->getSession()?->set('values', $newValues);
  }
}
