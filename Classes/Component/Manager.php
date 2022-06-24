<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Component;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Component Manager originally written for the extension 'gimmefive'.
 * This is a backport of the Component Manager of FLOW3. It's based
 * on code mainly written by Robert Lemke. Thanx to the FLOW3 team for all the great stuff!
 *
 * Refactored for usage with Formhandler.
 */
class Manager implements SingletonInterface {
  /**
   * The global Formhandler values.
   */
  protected Globals $globals;

  /**
   * The global Formhandler values.
   */
  protected \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs;

  public function __construct() {
    $this->globals = GeneralUtility::makeInstance(Globals::class);
    $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
  }
}
