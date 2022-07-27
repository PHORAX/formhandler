<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Debugger;

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
 * An abstract debugger.
 */
abstract class AbstractDebugger extends AbstractComponent {
  /** @var array<string, array<int, array{message: string, severity: int, data: array<int|string, mixed>}>> */
  protected array $debugLog = [];

  /**
   * Adds a message to the internal message storage.
   *
   * @param string                   $message  The message to log
   * @param int                      $severity The severity of the message (1,2,3)
   * @param array<int|string, mixed> $data     Additional data to log
   */
  public function addToDebugLog(string $message = '', int $severity = 1, array $data = []): void {
    $trace = debug_backtrace();
    $section = '';
    if (isset($trace[2])) {
      $section = strval($trace[2]['class'] ?? '');
      if ('\Typoheads\Formhandler\Utility\GeneralUtility' === $section) {
        $section = strval($trace[3]['class'] ?? '');
      }
    }
    if (!isset($this->debugLog[$section])) {
      $this->debugLog[$section] = [];
    }
    $this->debugLog[$section][] = ['message' => $message, 'severity' => $severity, 'data' => $data];
  }

  /**
   * Called if all messages were added to the internal message storage.
   * The component decides how to output the messages.
   */
  abstract public function outputDebugLog(): void;

  /**
   * The main method called by the controller.
   *
   * @return array<string, mixed> The probably modified GET/POST parameters
   */
  public function process(mixed &$error = null): array {
    // Not available for this type of component
    return [];
  }
}
