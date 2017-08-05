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
 * A simple debugger writing messages into devlog
 */
class DevLog extends AbstractDebugger
{

    /**
     * Inserts the messages to the devlog
     *
     * @return void
     */
    public function outputDebugLog()
    {
        foreach ($this->debugLog as $section => $logData) {
            foreach ($logData as $messageData) {
                $message = $section . ': ' . $messageData['message'];
                $data = false;
                if (is_array($messageData['data'])) {
                    $data = $messageData['data'];
                }
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, 'formhandler', $severity, $data);
            }
        }
    }
}
