<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Ajax;

use Psr\Http\Message\ResponseInterface;
use Typoheads\Formhandler\Component\AbstractClass;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Utility\GeneralUtility;
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
 * Abstract class for an Ajax.
 */
abstract class AbstractAjax extends AbstractClass {
  /**
   * Initialize the AjaxHandler.
   *
   * @param array<string, mixed> $settings The settings of the Ajax class
   */
  public function init(Manager $componentManager, Globals $globals, array $settings, GeneralUtility $utilityFuncs): void {
    $this->componentManager = $componentManager;
    $this->globals = $globals;
    $this->settings = $settings;
    $this->utilityFuncs = $utilityFuncs;
  }

  /**
   * Main method of the class.
   */
  abstract public function main(): ResponseInterface;
}
