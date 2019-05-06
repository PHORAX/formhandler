<?php
namespace Typoheads\Formhandler\Finisher;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This finisher stores the submitted values into a table in a different database than the TYPO3 database according to the configuration.
 *
 * Example configuration:
 *
 * <code>
 * finishers.1.class = Finisher\DifferentDB
 * finishers.1.config.host = 127.0.0.1
 * finishers.1.config.port = 666
 * finishers.1.config.db = typo3_421
 * finishers.1.config.username = root
 * finishers.1.config.password = rootpass
 * finishers.1.config.driver = oci8
 * </code>
 *
 * Further configuration equals the configuration of Finisher\DB.
 * @see \Typoheads\Formhandler\Finisher\DB
 */
class DifferentDB extends DB
{
    /**
     * Inits the finisher mapping settings values to internal attributes.
     *
     * @see \Typoheads\Formhandler\Finisher\DB::init
     * @return void
     */
    public function init($gp, $settings)
    {
        //read settings
        $connectionParams = [
            'dbname' => $this->utilityFuncs->getSingle($settings, 'db'),
            'user' => $this->utilityFuncs->getSingle($settings, 'username'),
            'password' => $this->utilityFuncs->getSingle($settings, 'password'),
            'charset' => 'utf8',
            'host' => $this->utilityFuncs->getSingle($settings, 'host'),
            'port' => $this->utilityFuncs->getSingle($settings, 'port'),
            'driver' => $this->utilityFuncs->getSingle($settings, 'driver') ?: 'mysqli',
            'initCommands' => $this->utilityFuncs->getSingle($settings, 'setDBinit')
        ];

        $connectionName = uniqid('formhandler_');
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] = $connectionParams;
        $this->connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName($connectionName);

        parent::init($gp, $settings);
    }

}
