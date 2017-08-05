<?php
namespace Typoheads\Formhandler\Debugger;

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
 * An abstract debugger
 * @abstract
 */
abstract class AbstractDebugger extends \Typoheads\Formhandler\Component\AbstractComponent
{
    protected $debugLog = [];

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        //Not available for this type of component
    }

    /**
     * Adds a message to the internal message storage
     *
     * @param string $message The message to log
     * @param int $severity The severity of the message (1,2,3)
     * @param array $data Additional data to log
     * @return void
     */
    public function addToDebugLog($message = '', $severity = 1, array $data = [])
    {
        $trace = debug_backtrace();
        $section = '';
        if (isset($trace[2])) {
            $section = $trace[2]['class'];
            if ($section === '\Typoheads\Formhandler\Utility\GeneralUtility') {
                $section = $trace[3]['class'];
            }
        }
        if (!$message && !isset($this->debugLog[$section])) {
            $this->debugLog[$section] = [];
        }
        if ($message) {
            $this->debugLog[$section][] = ['message' => $message, 'severity' => $severity, 'data' => $data];
        }
    }

    /**
     * Called if all messages were added to the internal message storage.
     * The component decides how to output the messages.
     *
     * @return void/mixed
     */
    abstract public function outputDebugLog();
}
