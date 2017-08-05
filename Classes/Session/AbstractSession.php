<?php
namespace Typoheads\Formhandler\Session;

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
abstract class AbstractSession extends \Typoheads\Formhandler\Component\AbstractClass
{

    /**
     * An indicator if a session was already started
     *
     * @access protected
     * @var boolean
     */
    protected $started = false;

    /**
     * Starts a new session
     *
     * @return void
     */
    public function start()
    {
        if (intval($this->utilityFuncs->getSingle($this->settings, 'disableCookies')) === 1) {
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
     * @return void
     */
    abstract public function set($key, $value);

    /**
     * Sets multiple keys at once
     *
     * @param array $values key value pairs
     * @return void
     */
    abstract public function setMultiple($values);

    /**
     * Get the value of the given key
     *
     * @param string $key The key
     * @return string The value
     */
    abstract public function get($key);

    /**
     * Checks if a session exists
     *
     * @return boolean
     */
    abstract public function exists();

    /**
     * Resets all session values
     *
     * @return void
     */
    abstract public function reset();

    protected function getOldSessionThreshold()
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
     *
     * @return void
     */
    public function init($gp, $settings)
    {
        $this->gp = $gp;
        $this->settings = $settings;
    }
}
