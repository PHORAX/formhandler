<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Session;

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
 * An abstract session class for Formhandler.
 */
abstract class AbstractSession extends AbstractClass {
  /**
   * An indicator if a session was already started.
   */
  protected bool $started = false;

  /**
   * Checks if a session exists.
   */
  abstract public function exists(): bool;

  /**
   * Get the value of the given key.
   *
   * @param string $key The key
   *
   * @return mixed The value
   */
  abstract public function get(string $key): mixed;

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
   * Resets all session values.
   */
  abstract public function reset(): void;

  /**
   * Sets a key.
   *
   * @param string $key   The key
   * @param mixed  $value The value to set
   */
  abstract public function set(string $key, mixed $value): void;

  /**
   * Sets multiple keys at once.
   *
   * @param array<string, mixed> $values key value pairs
   */
  abstract public function setMultiple(array $values): void;

  /**
   * Starts a new session.
   */
  public function start(): void {
    if (isset($this->settings) && !empty($this->settings) && 1 === (int) ($this->utilityFuncs->getSingle($this->settings, 'disableCookies'))) {
      ini_set('session.use_only_cookies', 'off');
      ini_set('session.use_cookies', '0');
    }
    if (!$this->started) {
      $current_session_id = session_id();
      if (empty($current_session_id)) {
        session_start();
      }
      $this->started = true;
    }
  }

  protected function getOldSessionThreshold(): int {
    $threshold = $this->utilityFuncs->getTimestamp(1, 'hours');

    if (isset($this->settings['clearSessionsOlderThan.'], $this->settings['clearSessionsOlderThan.']['value'])) {
      $thresholdValue = (int) $this->utilityFuncs->getSingle($this->settings['clearSessionsOlderThan.'], 'value');
      $thresholdUnit = $this->utilityFuncs->getSingle($this->settings['clearSessionsOlderThan.'], 'unit');

      $threshold = $this->utilityFuncs->getTimestamp($thresholdValue, $thresholdUnit);
    }

    return $threshold;
  }
}
