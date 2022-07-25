<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

use Typoheads\Formhandler\Component\AbstractComponent;

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
 * Abstract class for Finisher Classes used by Formhandler.
 */
abstract class AbstractFinisher extends AbstractComponent {
  /**
   * defined Error Class.
   */
  protected ResponseError $error;

  /**
   * Initialize the class variables.
   *
   * @param array<string, mixed> $gp       GET and POST variable array
   * @param array<string, mixed> $settings Typoscript configuration for the component (component.1.config.*)
   */
  public function init(array $gp, array $settings): void {
    $this->gp = $gp;
    $this->settings = $settings;
    $this->error = new ResponseError();
  }
}
