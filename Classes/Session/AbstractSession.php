<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Session;

use Typoheads\Formhandler\Component\AbstractClass;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/**
 * An abstract session class for Formhandler
 */
abstract class AbstractSession extends AbstractClass
{

    /**
     * An indicator if a session was already started
     *
     * @var bool
     */
    protected bool $started = false;

    /**
     * Starts a new session
     */
    public function start(): void
    {
        if ((int)($this->utilityFuncs->getSingle($this->settings, 'disableCookies')) === 1) {
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

    /**
     * Sets a key
     *
     * @param string $key The key
     * @param string $value The value to set
     */
    abstract public function set(string $key, string $value): void;

    /**
     * Sets multiple keys at once
     *
     * @param array $values key value pairs
     */
    abstract public function setMultiple(array $values): void;

    /**
     * Get the value of the given key
     *
     * @param string $key The key
     * @return string The value
     */
    abstract public function get(string $key): string;

    /**
     * Checks if a session exists
     *
     * @return bool
     */
    abstract public function exists(): bool;

    /**
     * Resets all session values
     */
    abstract public function reset(): void;

    protected function getOldSessionThreshold(): int
    {
        $threshold = $this->utilityFuncs->getTimestamp(1, 'hours');

        if ($this->settings['clearSessionsOlderThan.']['value']) {
            $thresholdValue = $this->utilityFuncs->getSingle($this->settings['clearSessionsOlderThan.'], 'value');
            $thresholdUnit = $this->utilityFuncs->getSingle($this->settings['clearSessionsOlderThan.'], 'unit');

            $threshold = $this->utilityFuncs->getTimestamp($thresholdValue, $thresholdUnit);
        }
        return $threshold;
    }

    /**
     * Initialize the class variables
     *
     * @param array $gp GET and POST variable array
     * @param array $settings Typoscript configuration for the component (component.1.config.*)
     */
    public function init(array $gp, array $settings): void
    {
        $this->gp = $gp;
        $this->settings = $settings;
    }
}
