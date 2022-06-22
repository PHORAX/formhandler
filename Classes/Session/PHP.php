<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Session;

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
 * A session class for Formhandler using PHP sessions.
 */
class PHP extends AbstractSession {
  /* (non-PHPdoc)
   * @see Classes/Session/Tx_Formhandler_AbstractSession#exists()
  */
  public function exists(): bool {
    $this->start();
    $data = $_SESSION['formhandler'] ?? [];

    return isset($data[$this->globals->getRandomID()]) && is_array($data[$this->globals->getRandomID()]);
  }

  /* (non-PHPdoc)
   * @see Classes/Session/Tx_Formhandler_AbstractSession#get()
  */
  public function get(string $key): mixed {
    $this->start();
    $data = $_SESSION['formhandler'] ?? [];
    if (!isset($data[$this->globals->getRandomID()]) || !is_array($data[$this->globals->getRandomID()])) {
      $data[$this->globals->getRandomID()] = [];
    }

    return $data[$this->globals->getRandomID()][$key] ?? null;
  }

  /**
   * Initialize the class variables.
   *
   * @param array<string, mixed> $gp       GET and POST variable array
   * @param array<string, mixed> $settings Typoscript configuration for the component (component.1.config.*)
   */
  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);

    if (isset($_SESSION['formhandler']) && is_array($_SESSION['formhandler'])) {
      foreach ($_SESSION['formhandler'] as $hashedID => $sesData) {
        $threshold = $this->getOldSessionThreshold();
        if ((!isset($this->gp['submitted']) || !$this->gp['submitted']) && $this->globals->getFormValuesPrefix() === $sesData['formValuesPrefix'] && $sesData['creationTstamp'] < $threshold) {
          unset($_SESSION['formhandler'][$hashedID]);
        }
      }
    } else {
      $_SESSION['formhandler'] = [];
    }
  }

  /** (non-PHPdoc).
   * @see Classes/Session/Tx_Formhandler_AbstractSession#reset()
   */
  public function reset(): void {
    $this->start();
    if (isset($_SESSION['formhandler'], $_SESSION['formhandler'][$this->globals->getRandomID()])) {
      unset($_SESSION['formhandler'][$this->globals->getRandomID()]);
    }
  }

  /** (non-PHPdoc).
   * @see Classes/Session/Tx_Formhandler_AbstractSession#set()
   */
  public function set(string $key, mixed $value): void {
    $this->start();
    $data = $_SESSION['formhandler'] ?? [];
    if (!isset($data[$this->globals->getRandomID()]) || !is_array($data[$this->globals->getRandomID()])) {
      $data[$this->globals->getRandomID()] = [];
    }
    $data[$this->globals->getRandomID()][$key] = $value;
    $_SESSION['formhandler'] = $data;
  }

  /**  (non-PHPdoc).
   * @see Classes/Session/Tx_Formhandler_AbstractSession#setMultiple()
   *
   * @param array<string, mixed> $values
   */
  public function setMultiple(array $values): void {
    if (is_array($values) && !empty($values)) {
      $this->start();
      $data = $_SESSION['formhandler'] ?? [];
      if (!isset($data[$this->globals->getRandomID()]) || !is_array($data[$this->globals->getRandomID()])) {
        $data[$this->globals->getRandomID()] = [];
      }
      foreach ($values as $key => $value) {
        $data[$this->globals->getRandomID()][$key] = $value;
      }
      $_SESSION['formhandler'] = $data;
    }
  }
}
