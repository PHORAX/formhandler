<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use Typoheads\Formhandler\Component\AbstractClass;

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
 * Abstract class for Controller Classes used by Formhandler.
 */
abstract class AbstractController extends AbstractClass {
  /**
   * The content returned by the controller.
   */
  protected Content $content;

  /**
   * Array of configured translation files.
   *
   * @var array<int, null|string>
   */
  protected array $langFiles = [];

  /**
   * The key of a possibly selected predefined form.
   */
  protected string $predefined = '';

  /**
   * The template file to be used. Only if template file was defined via plugin record.
   */
  protected string $templateFile = '';

  /**
   * Returns the content attribute of the controller.
   */
  public function getContent(): Content {
    return $this->content;
  }

  /**
   * Returns the right settings for the formhandler (Checks if predefined form was selected).
   *
   * @return array<string, mixed> The settings
   */
  public function getSettings(): array {
    $settings = $this->configuration->getSettings();
    if ($this->predefined && isset($settings['predef.']) && is_array($settings['predef.']) && isset($settings['predef.'][$this->predefined]) && is_array($settings['predef.'][$this->predefined])) {
      $predefSettings = $settings['predef.'][$this->predefined];
      unset($settings['predef.']);
      $settings = $this->utilityFuncs->mergeConfiguration($settings, $predefSettings);
    }

    return $settings;
  }

  /**
   * Sets the content attribute of the controller.
   */
  public function setContent(Content $content): void {
    $this->content = $content;
  }

  /**
   * Sets the internal attribute "langFile".
   *
   * @param array<int, null|string> $langFiles
   */
  public function setLangFiles(array $langFiles): void {
    $this->langFiles = $langFiles;
  }

  /**
   * Sets the internal attribute "predefined".
   */
  public function setPredefined(string $key): void {
    $this->predefined = $key;
  }

  /**
   * Sets the template file attribute to $template.
   */
  public function setTemplateFile(string $template): void {
    $this->templateFile = $template;
  }
}
