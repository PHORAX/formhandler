<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\View;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use Typoheads\Formhandler\Component\Manager;
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
 * An abstract view for Formhandler.
 */
abstract class AbstractView extends AbstractPlugin {
  /**
   * The Formhandler component manager.
   */
  protected Manager $componentManager;

  /** @var array<string, mixed> */
  protected array $componentSettings = [];

  /**
   * The global Formhandler configuration.
   */
  protected Configuration $configuration;

  /**
   * The global Formhandler values.
   */
  protected Globals $globals;

  /**
   * The get/post parameters.
   *
   * @var array<string, mixed>
   */
  protected array $gp = [];

  /**
   * An array of translation file names.
   *
   * @var array<string, mixed>
   */
  protected array $langFiles = [];

  protected MarkerBasedTemplateService $markerBasedTemplateService;

  /**
   * The predefined.
   */
  protected string $predefined = '';

  /** @var array<string, mixed> */
  protected array $settings = [];

  /**
   * The subparts array.
   *
   * @var array<string, mixed>
   */
  protected array $subparts = [];

  /**
   * The template code.
   */
  protected string $template = '';

  /**
   * The Formhandler utility methods.
   */
  protected GeneralUtility $utilityFuncs;

  /**
   * The constructor for a view setting the component manager and the configuration.
   */
  public function __construct(
    Manager $componentManager,
    Configuration $configuration,
    Globals $globals,
    GeneralUtility $utilityFuncs
  ) {
    parent::__construct();
    $this->componentManager = $componentManager;
    $this->configuration = $configuration;
    $this->globals = $globals;
    $this->utilityFuncs = $utilityFuncs;
    $this->cObj = $this->globals->getCObj();
    $this->markerBasedTemplateService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    $this->pi_loadLL();
    $this->initializeView();
  }

  /**
   * @return array<string, mixed>
   */
  public function getComponentSettings(): array {
    return $this->componentSettings;
  }

  /**
   * Returns false if the view doesn't have template code.
   */
  public function hasTemplate(): bool {
    return !empty($this->subparts['template']);
  }

  /**
   * This method performs the rendering of the view.
   *
   * @param array<string, mixed> $gp     The get/post parameters
   * @param array<string, mixed> $errors An array with errors occurred whilest validation
   *
   * @return string rendered view
   */
  abstract public function render(array $gp, array $errors): string;

  /**
   * @param array<string, mixed> $settings
   */
  public function setComponentSettings(array $settings): void {
    $this->componentSettings = $settings;
  }

  /**
   * Sets the internal attribute "langFiles".
   *
   * @param array<string, mixed> $langFiles The files array
   */
  public function setLangFiles(array $langFiles): void {
    $this->langFiles = $langFiles;
  }

  /**
   * Sets the key of the chosen predefined form.
   *
   * @param string $key The key of the predefined form
   */
  public function setPredefined(string $key): void {
    $this->predefined = $key;
  }

  /**
   * Sets the settings.
   *
   * @param array<string, mixed> $settings The settings
   */
  public function setSettings(array $settings): void {
    $this->settings = $settings;
  }

  /**
   * Sets the template of the view.
   *
   * @param string $templateCode  The whole template code of a template file
   * @param string $templateName  Name of a subpart containing the template code to work with
   * @param bool   $forceTemplate Not needed
   */
  public function setTemplate(string $templateCode, string $templateName, bool $forceTemplate = false): void {
    $this->subparts['template'] = $this->markerBasedTemplateService->getSubpart($templateCode, '###TEMPLATE_'.$templateName.'###');
    $this->subparts['item'] = $this->markerBasedTemplateService->getSubpart($this->subparts['template'], '###ITEM###');
  }

  /**
   * Returns given string in uppercase.
   *
   * @param string $camelCase The string to transform
   *
   * @return string Parsed string
   *
   * @author Jochen Rau
   */
  protected function getUpperCase(string $camelCase): string {
    return strtoupper(preg_replace('/\p{Lu}+(?!\p{Ll})|\p{Lu}/u', '_$0', $camelCase));
  }

  /**
   * Overwrite this method to extend the initialization of the View.
   *
   * @author Jochen Rau
   */
  protected function initializeView(): void {
  }
}
