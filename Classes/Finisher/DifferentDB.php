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

/**
 * This finisher stores the submitted values into a table in a different database than the TYPO3 database according to the configuration.
 * This class uses the extension 'adodb' to query the database.
 *
 * Example configuration:
 *
 * <code>
 * finishers.1.class = Tx_Formhandler_Finisher_DifferentDB
 * finishers.1.config.host = 127.0.0.1
 * finishers.1.config.port = 666
 * finishers.1.config.db = typo3_421
 * finishers.1.config.username = root
 * finishers.1.config.password = rootpass
 * finishers.1.config.driver = oci8
 * </code>
 *
 * Further configuration equals the configuration of Tx_Formhandler_Finisher_DB.
 *
 * @author    Reinhard Führicht <rf@typoheads.at>
 * @see Tx_Formhandler_Finisher_DB
 */
class DifferentDB extends DB
{

    /**
     * The name of the database driver to use.
     *
     * @access protected
     * @var string
     */
    protected $driver;

    /**
     * The name of the database host.
     *
     * @access protected
     * @var string
     */
    protected $host;

    /**
     * The port the database listens.
     *
     * @access protected
     * @var integer
     */
    protected $port;

    /**
     * The name of the database.
     *
     * @access protected
     * @var string
     */
    protected $db;

    /**
     * The username to use.
     *
     * @access protected
     * @var string
     */
    protected $user;

    /**
     * The password to use.
     *
     * @access protected
     * @var string
     */
    protected $password;

    /**
     * Method to query the database making an insert or update statement using the given fields.
     *
     * @see \Typoheads\Formhandler\Finisher\DB::save()
     * @param array &$queryFields Array holding the query fields
     * @return void
     */
    protected function save(&$queryFields)
    {

        //if adodb is installed
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')) {

            //insert query
            if (!$this->doUpdate) {
                $query = $GLOBALS['TYPO3_DB']->INSERTquery($this->table, $queryFields);
                $this->utilityFuncs->debugMessage('sql_request', [$query]);

                //update query
            } else {

                //check if uid of record to update is in GP
                $uid = $this->getUpdateUid();
                $uid = $GLOBALS['TYPO3_DB']->fullQuoteStr($uid, $this->table);
                if ($uid) {
                    $query = $GLOBALS['TYPO3_DB']->UPDATEquery($this->table, $this->key . '=' . $uid, $queryFields);
                    $this->utilityFuncs->debugMessage('sql_request', [$query]);
                } else {
                    $this->utilityFuncs->debugMessage('no_update_possible', [], 2);
                }
            }

            //open connection
            $conn = &NewADOConnection($this->driver);
            $host = $this->host;
            if ($this->port) {
                $host .= ':' . $this->port;
            }
            if ($this->db) {
                $conn->Connect($host, $this->user, $this->password, $this->db);
            } else {
                $conn->Connect($host, $this->user, $this->password);
            }

            if ($this->settings['setDBinit']) {
                $conn->Execute($this->utilityFuncs->getSingle($this->settings, 'setDBinit'));
            }

            //insert data
            $conn->Execute($query);

            //close connection
            $conn->Close();
        }
    }

    protected function doesRecordExist($uid)
    {
        $exists = FALSE;
        if ($uid) {
            $uid = $GLOBALS['TYPO3_DB']->fullQuoteStr($uid, $this->table);

            //if adodb is installed (already tested in init, but used to show adodb is used)
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')) {

                //open connection
                $conn = &NewADOConnection($this->driver);

                $host = $this->host;

                if ($this->port) {
                    $host .= ':' . $this->port;
                }

                if ($this->db) {
                    $conn->Connect($host, $this->user, $this->password, $this->db);
                } else {
                    $conn->Connect($host, $this->user, $this->password);
                }

                $query = $GLOBALS['TYPO3_DB']->SELECTquery($this->key, $this->table, $this->key . '=' . $uid);

                $temp = $conn->Execute($query);
                $res = count($temp->GetArray());

                //close connection
                $conn->Close();
            }

            if ($res > 0) {
                $exists = TRUE;
            }
        }

        return $exists;
    }

    /**
     * Inits the finisher mapping settings values to internal attributes.
     *
     * @see Tx_Formhandler_Finisher_DB::init
     * @return void
     */
    public function init($gp, $settings)
    {

        //if adodb is installed
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')) {
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('adodb') . 'adodb/adodb.inc.php');

            $this->driver = $this->utilityFuncs->getSingle($settings, 'driver');
            $this->db = $this->utilityFuncs->getSingle($settings, 'db');
            $this->host = $this->utilityFuncs->getSingle($settings, 'host');
            $this->port = $this->utilityFuncs->getSingle($settings, 'port');
            $this->user = $this->utilityFuncs->getSingle($settings, 'username');
            $this->password = $this->utilityFuncs->getSingle($settings, 'password');
            if (!$this->driver) {
                throw new \Exception('No driver given!');
            }
        } else {
            $this->utilityFuncs->throwException('extension_required', 'adodb', '\\Typoheads\\Formhandler\\Finisher\\DifferentDB');
        }

        parent::init($gp, $settings);
    }

}
