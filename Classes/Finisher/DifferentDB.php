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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * This finisher stores the submitted values into a table in a different database than the TYPO3 database according to the configuration.
 * This class uses the extension 'adodb' to query the database.
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
     * SQL Statement executed on DB initialization
     * @var string
     */
    protected $setDBinit;

    /**
     * The password to use.
     *
     * @access protected
     * @var string
     */
    protected $password;

    /**
     * The connection object.
     *
     * @access protected
     * @var \ADOConnection
     */
    protected $connection;

    protected function doInsert($queryFields)
    {
        $isSuccess = true;

        // get insert query
        $query = $GLOBALS['TYPO3_DB']->INSERTquery($this->table, $queryFields);
        $this->utilityFuncs->debugMessage('sql_request', [$query]);

        // execute query
        $this->connection->Execute($query);

        // error occured?
        if ($this->connection->ErrorNo() != 0) {
            $ErrorMsg = $this->connection->ErrorMsg();
            $this->utilityFuncs->debugMessage('sql_request_error', [$ErrorMsg], 3);
            $isSuccess = false;
        }

        return $isSuccess;
    }

    protected function doUpdate($uid, $queryFields, $andWhere)
    {
        $isSuccess = true;

        // build update query
        $uid = $GLOBALS['TYPO3_DB']->fullQuoteStr($uid, $this->table);
        $andWhere = $this->utilityFuncs->prepareAndWhereString($andWhere);
        $query = $GLOBALS['TYPO3_DB']->UPDATEquery($this->table, $this->key . '=' . $uid . $andWhere, $queryFields);
        $this->utilityFuncs->debugMessage('sql_request', [$query]);

        // execute query
        $this->connection->Execute($query);

        if ($this->connection->ErrorNo() != 0) {
            $ErrorMsg = $this->connection->ErrorMsg();
            $this->utilityFuncs->debugMessage('sql_request_error', [$ErrorMsg], 3);
            $isSuccess = false;
        }

        return $isSuccess;
    }

    protected function doesRecordExist($uid, $andWhere)
    {
        $exists = false;
        
        if ($uid) {
            $uid = $GLOBALS['TYPO3_DB']->fullQuoteStr($uid, $this->table);
            $andWhere = $this->utilityFuncs->prepareAndWhereString($andWhere);
            $query = $GLOBALS['TYPO3_DB']->SELECTquery($this->key, $this->table, $this->key . '=' . $uid . $andWhere);

            /** @var \ADORecordSet $rs */
            $rs = $this->connection->Execute($query);
            
            if ($rs->RecordCount() > 0) {
                $exists = true;
            }

            $rs->Close();
        }

        return $exists;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInsertedUid()
    {
        $uid = $this->connection->Insert_ID();
        return intval($uid);
    }

    /**
     * Inits the finisher mapping settings values to internal attributes.
     *
     * @see \Typoheads\Formhandler\Finisher\DB::init
     * @return void
     */
    public function init($gp, $settings)
    {
        //if adodb is not loaded
        if (!ExtensionManagementUtility::isLoaded('adodb')) {
            $this->utilityFuncs->throwException('extension_required', 'adodb', '\\Typoheads\\Formhandler\\Finisher\\DifferentDB');
        }

        //include sources
        require_once(ExtensionManagementUtility::extPath('adodb') . 'adodb/adodb.inc.php');

        //read settings
        $this->driver = $this->utilityFuncs->getSingle($settings, 'driver');
        $this->db = $this->utilityFuncs->getSingle($settings, 'db');
        $this->host = $this->utilityFuncs->getSingle($settings, 'host');
        $this->port = $this->utilityFuncs->getSingle($settings, 'port');
        $this->user = $this->utilityFuncs->getSingle($settings, 'username');
        $this->password = $this->utilityFuncs->getSingle($settings, 'password');
        $this->setDBinit = $this->utilityFuncs->getSingle($settings, 'setDBinit');

        //if no driver set
        if (!$this->driver) {
            $this->utilityFuncs->throwException('no_driver', '\\Typoheads\\Formhandler\\Finisher\\DifferentDB');
        }

        //open DB connection now
        $this->connect();

        parent::init($gp, $settings);
    }

    /**
     * Create DB connection
     */
    protected function connect()
    {
        /** @var $this->connection \ADOConnection */
        $this->connection = &NewADOConnection($this->driver);

        $host = $this->host;

        if ($this->port) {
            $host .= ':' . $this->port;
        }

        // connect
        if ($this->db) {
            $this->connection->Connect($host, $this->user, $this->password, $this->db);
        } else {
            $this->connection->Connect($host, $this->user, $this->password);
        }

        // check connection
        if (!$this->connection->IsConnected()) {
            $errMsg = $this->connection->ErrorMsg();
            $this->utilityFuncs->throwException('db_connection_failed', $errMsg);
        }

        // execute initial statement
        if ($this->setDBinit) {
            $this->utilityFuncs->debugMessage('sql_request', [$this->setDBinit]);
            $this->connection->Execute($this->setDBinit);

            // error occured?
            if ($this->connection->ErrorNo() != 0) {
                $errMsg = $this->connection->ErrorMsg();
                $this->utilityFuncs->debugMessage('sql_request_error', [$errMsg], 3);
            }
        }
    }

    /**
     * Disconnect DB
     */
    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->Close();
        }
    }
}
