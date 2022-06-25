<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Component;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Typoheads\Formhandler\Controller\Configuration;
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
 * Abstract class for any usable Formhandler component.
 * This class defines some useful variables and a default constructor for all Formhandler components.
 */
abstract class AbstractClass {
  /**
   * The cObj.
   */
  protected ContentObjectRenderer $cObj;

  /**
   * The Formhandler component manager.
   */
  protected Manager $componentManager;

  /**
   * The global Formhandler configuration.
   */
  protected Configuration $configuration;

  /**
   * The global Formhandler values.
   */
  protected Globals $globals;

  /**
   * The GET/POST parameters.
   *
   * @var array<string, mixed>
   */
  protected array $gp = [];

  /**
   * The predefined.
   */
  protected string $predefined = '';

  /**
   * Settings.
   *
   * @var array<string, mixed>
   */
  protected array $settings = [];

  /**
   * The template code.
   */
  protected string $template = '';

  /**
   * The Formhandler utility methods.
   */
  protected GeneralUtility $utilityFuncs;

  /**
   * @var array<string, mixed>
   */
  protected array $validationStatusClasses = [];

  /**
   * The constructor for an interceptor setting the component manager and the configuration.
   */
  public function __construct(
    Manager $componentManager,
    Configuration $configuration,
    Globals $globals,
    GeneralUtility $utilityFuncs
  ) {
    $this->componentManager = $componentManager;
    $this->configuration = $configuration;
    $this->globals = $globals;
    $this->utilityFuncs = $utilityFuncs;
    if (null !== $this->globals->getCObj()) {
      $this->cObj = $this->globals->getCObj();
    }
  }
}
