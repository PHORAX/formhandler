<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Component;

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
 * Abstract component class for any usable Formhandler component.
 * This class extends the abstract class and adds some useful variables and methods.
 */
abstract class AbstractComponent extends AbstractClass {
  /**
   * Initialize the class variables.
   *
   * @param array<string, mixed> $gp       GET and POST variable array
   * @param array<string, mixed> $settings Typoscript configuration for the component (component.1.config.*)
   */
  public function init(array $gp, array $settings): void {
    $this->gp = $gp;
    $this->settings = $settings;
  }

  /**
   * The main method called by the controller.
   *
   * @return array<string, mixed>|string The probably modified GET/POST parameters
   */
  abstract public function process(mixed &$error = null): array|string;

  public function validateConfig(): bool {
    return true;
  }
}
