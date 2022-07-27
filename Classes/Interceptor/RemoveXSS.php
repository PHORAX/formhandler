<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Interceptor;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * An interceptor doing XSS checking on GET/POST parameters.
 */
class RemoveXSS extends AbstractInterceptor {
  /** @var string[] */
  private array $doNotSanitizeFields = [];

  /** @var string[] */
  private array $removeChars = [];

  /* (non-PHPdoc)
   * @see Classes/Component/\Typoheads\Formhandler\Component\AbstractComponent#init($gp, $settings)
  */
  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);
    if (isset($this->settings['doNotSanitizeFields']) && (bool) $this->settings['doNotSanitizeFields']) {
      $this->doNotSanitizeFields = GeneralUtility::trimExplode(',', $this->utilityFuncs->getSingle($this->settings, 'doNotSanitizeFields'));
    }
  }

  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    // search for a global setting for character removal
    $globalSetting = (array) (((array) ($this->settings['fieldConf.'] ?? []))['global.'] ?? []);
    if (isset($globalSetting['removeChars'])) {
      $sep = ',';

      // user set custom rules via cObject
      $cObjSettings = $globalSetting['removeChars.'];
      if (is_array($cObjSettings)) {
        $list = $this->utilityFuncs->getSingle((array) $globalSetting, 'removeChars');

        // user set custom separator
        if ($globalSetting['separator']) {
          $sep = $this->utilityFuncs->getSingle((array) $globalSetting, 'separator');
        }
      } else {
        // user entered a comma seperated list
        $list = strval($globalSetting['removeChars']);
      }
      $this->removeChars = GeneralUtility::trimExplode($sep, $list);
    } elseif (1 === (int) $this->utilityFuncs->getSingle((array) ($globalSetting['removeChars.'] ?? []), 'disable')) {
      // user disabled removal globally
      $this->removeChars = [];
    }
    $this->gp = $this->sanitizeValues($this->gp);

    return $this->gp;
  }

  /**
   * This method does XSS checks and escapes malicious data.
   *
   * @param array<string, mixed> $values The GET/POST parameters
   *
   * @return array<string, mixed> The sanitized GET/POST parameters
   */
  public function sanitizeValues(array $values): array {
    $sanitizedArray = [];
    foreach ($values as $key => $value) {
      if (!in_array($key, $this->doNotSanitizeFields) && is_array($value)) {
        $sanitizedArray[$key] = $this->sanitizeValues($value);
      } elseif (!in_array($key, $this->doNotSanitizeFields) && is_string($value) && strlen(trim($value)) > 0) {
        $removeChars = $this->removeChars;

        // search for a specific setting for this field
        $fieldSetting = (array) (((array) ($this->settings['fieldConf.'] ?? []))[$key.'.'] ?? []);
        if (isset($fieldSetting['removeChars'])) {
          $sep = ',';

          // user set custom rules via cObject
          $cObjSettings = $fieldSetting['removeChars.'];
          if (is_array($cObjSettings)) {
            $list = $this->utilityFuncs->getSingle((array) $fieldSetting, 'removeChars');

            // user set custom separator
            if ($fieldSetting['separator']) {
              $sep = $this->utilityFuncs->getSingle((array) $fieldSetting, 'separator');
            }
          } else {
            // user entered a comma seperated list
            $list = strval($fieldSetting['removeChars']);
          }
          $removeChars = GeneralUtility::trimExplode($sep, $list);
        } elseif (1 === (int) $this->utilityFuncs->getSingle((array) ($fieldSetting['removeChars.'] ?? []), 'disable')) {
          // user disabled removal for this field
          $removeChars = [];
        }

        $value = str_replace("\t", '', $value);
        $value = str_replace($removeChars, ' ', $value);

        $isUTF8 = $this->isUTF8($value);
        if (!$isUTF8) {
          $value = utf8_encode($value);
        }
        $value = htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML401);

        if (!$isUTF8) {
          $value = utf8_decode($value);
        }
        $sanitizedArray[$key] = $value;
      } else {
        $sanitizedArray[$key] = $value;
      }
    }

    return $sanitizedArray;
  }

  /**
   * This method detects if a given input string if valid UTF-8.
   *
   * @author hmdker <hmdker(at)gmail(dot)com>
   *
   * @return bool is UTF-8
   */
  protected function isUTF8(string $str): bool {
    $len = strlen($str);
    for ($i = 0; $i < $len; ++$i) {
      $c = ord($str[$i]);
      if ($c > 128) {
        if ($c >= 254) {
          return false;
        }
        if ($c >= 252) {
          $bits = 6;
        } elseif ($c >= 248) {
          $bits = 5;
        } elseif ($c >= 240) {
          $bits = 4;
        } elseif ($c >= 224) {
          $bits = 3;
        } elseif ($c >= 192) {
          $bits = 2;
        } else {
          return false;
        }
        if (($i + $bits) > $len) {
          return false;
        }
        while ($bits > 1) {
          ++$i;
          $b = ord($str[$i]);
          if ($b < 128 || $b > 191) {
            return false;
          }
          --$bits;
        }
      }
    }

    return true;
  }
}
