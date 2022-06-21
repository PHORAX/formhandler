<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Utility;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Typoheads\Formhandler\AjaxHandler\AbstractAjaxHandler;
use Typoheads\Formhandler\Session\AbstractSession;

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
 * A helper class for Formhandler to store global values.
 */
class Globals implements SingletonInterface {
  protected static ?AbstractAjaxHandler $ajaxHandler = null;

  protected static bool $ajaxMode = false;

  protected static ?ContentObjectRenderer $cObj = null;

  /** @var array<string, mixed> */
  protected static array $debuggers = [];

  protected static string $formID = '';

  protected static string $formValuesPrefix = '';

  /** @var array<string, mixed> */
  protected static array $gp = [];

  /** @var array<string, mixed> */
  protected static array $langFiles = [];

  /** @var array<string, mixed> */
  protected static array $overrideSettings = [];

  protected static string $predef = '';

  protected static string $randomID = '';

  protected static ?AbstractSession $session = null;

  /** @var array<string, mixed> */
  protected static array $settings = [];

  protected static bool $submitted = false;

  protected static string $templateCode = '';

  protected static string $templateSuffix = '';

  public static function addDebugger(mixed $debugger): void {
    if (!is_array(self::$debuggers)) {
      self::$debuggers = [];
    }
    self::$debuggers[] = $debugger;
  }

  public static function getAjaxHandler(): ?AbstractAjaxHandler {
    return self::$ajaxHandler;
  }

  public static function getCObj(): ?ContentObjectRenderer {
    return self::$cObj;
  }

  /**
   * @return array<string, mixed>
   */
  public static function getDebuggers(): array {
    return self::$debuggers;
  }

  public static function getFormID(): string {
    return self::$formID;
  }

  public static function getFormValuesPrefix(): string {
    return self::$formValuesPrefix;
  }

  /**
   * @return array<string, mixed>
   */
  public static function getGP(): array {
    return self::$gp;
  }

  /**
   * @return array<string, mixed>
   */
  public static function getLangFiles(): array {
    return self::$langFiles;
  }

  /**
   * @return array<string, mixed>
   */
  public static function getOverrideSettings(): array {
    return self::$overrideSettings;
  }

  public static function getPredef(): string {
    return self::$predef;
  }

  public static function getRandomID(): string {
    return self::$randomID;
  }

  public static function getSession(): ?AbstractSession {
    return self::$session;
  }

  /**
   * @return array<string, mixed>
   */
  public static function getSettings(): array {
    return self::$settings;
  }

  public static function getTemplateCode(): string {
    return self::$templateCode;
  }

  public static function getTemplateSuffix(): string {
    return self::$templateSuffix;
  }

  public static function isAjaxMode(): bool {
    return self::$ajaxMode;
  }

  public static function isSubmitted(): bool {
    return self::$submitted;
  }

  public static function setAjaxHandler(?AbstractAjaxHandler $ajaxHandler): void {
    self::$ajaxHandler = $ajaxHandler;
  }

  public static function setAjaxMode(bool $mode): void {
    self::$ajaxMode = $mode;
  }

  public static function setCObj(?ContentObjectRenderer $cObj): void {
    self::$cObj = $cObj;
  }

  /**
   * @param array<string, mixed> $debuggers
   */
  public static function setDebuggers(array $debuggers): void {
    self::$debuggers = $debuggers;
  }

  public static function setFormID(string $formID): void {
    self::$formID = $formID;
  }

  public static function setFormValuesPrefix(string $formValuesPrefix): void {
    self::$formValuesPrefix = $formValuesPrefix;
  }

  /**
   * @param array<string, mixed> $gp
   */
  public static function setGP(array $gp): void {
    self::$gp = $gp;
  }

  /**
   * @param array<string, mixed> $langFiles
   */
  public static function setLangFiles(array $langFiles): void {
    self::$langFiles = $langFiles;
  }

  /**
   * @param array<string, mixed> $overrideSettings
   */
  public static function setOverrideSettings(array $overrideSettings): void {
    self::$overrideSettings = $overrideSettings;
  }

  public static function setPredef(string $predef): void {
    self::$predef = $predef;
  }

  public static function setRandomID(string $randomID): void {
    self::$randomID = $randomID;
  }

  public static function setSession(?AbstractSession $session): void {
    self::$session = $session;
  }

  /**
   * @param array<string, mixed> $settings
   */
  public static function setSettings(array $settings): void {
    self::$settings = $settings;
  }

  public static function setSubmitted(bool $submitted): void {
    self::$submitted = $submitted;
  }

  public static function setTemplateCode(string $templateCode): void {
    self::$templateCode = $templateCode;
  }

  public static function setTemplateSuffix(string $templateSuffix): void {
    self::$templateSuffix = $templateSuffix;
  }
}
