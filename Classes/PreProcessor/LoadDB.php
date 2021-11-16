<?php
namespace Typoheads\Formhandler\PreProcessor;

use TYPO3\CMS\Core\Core\Environment;
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
 * This PreProcessor adds the posibility to load default values from database.
 * Values for the first step are loaded to $gp values of other steps are stored
 * to the session.
 *
 * Example configuration:
 *
 * <code>
 * preProcessors.1.class = Tx_Formhandler_PreProcessor_LoadDB
 * #DB setup (properties commented out are not required).
 * #All properties can be processed as cObjects (like TEXT and COA)
 * preProcessors.1.config.select {
 *       #selectFields = *
 *       table = my_custom_table
 *       #where =
 *       #groupBy =
 *       #orderBy =
 *       #limit =
 * }
 * preProcessors.1.config.1.contact_via.mapping = email
 * preProcessors.1.config.2.[field1].mapping = listfield
 * preProcessors.1.config.2.[field1].separator = ,
 * #The following allows for dynamic field names
 * preProcessors.1.config.2.[field2].mapping {
 *       data = page:subtitle
 *       wrap = field_|_xyz
 * }
 * preProcessors.1.config.2.[field3].mapping < plugin.tx_exampleplugin
 * <code>
 *
 *
 * @author    Mathias Bolt Lesniak, LiliO Design <mathias@lilio.com>
 */
class LoadDB extends AbstractPreProcessor
{

    /**
     * @var array $data as associative array. Row data from DB.
     */
    protected $data;

    /**
     * @var array $files as associative array.
     */
    protected $files;

    /**
     * Main method called by the controller
     *
     * @return array GP
     */
    public function process()
    {
        $this->data = $this->loadDB($this->settings['select.']);

        foreach ($this->settings as $step => $stepSettings) {
            $step = preg_replace('/\.$/', '', $step);

            if ($step !== 'select') {
                if (intval($step) === 1) {
                    $this->loadDBToGP($stepSettings);
                } elseif (is_numeric($step)) {
                    $this->loadDBToSession($stepSettings, $step);
                }
            }
        }

        return $this->gp;
    }

    /**
     * Loads data from DB intto the GP Array
     *
     * @param array $settings
     */
    protected function loadDBToGP($settings)
    {
        if (is_array($settings)) {
            $arrKeys = array_keys($settings);
            foreach ($arrKeys as $idx => $fieldname) {
                $fieldname = preg_replace('/\.$/', '', $fieldname);
                if (!isset($this->gp[$fieldname])) {
                    $this->gp[$fieldname] = $this->parseValue($fieldname, $settings);
                }
            }
        }
    }

    /**
     * Loads DB data into the Session. Used only for step 2+.
     *
     * @param array $settings
     * @param int $step
     */
    protected function loadDBToSession($settings, $step)
    {
        session_start();
        if (is_array($settings) && $step) {
            $values = $this->globals->getSession()->get('values');
            $arrKeys = array_keys($settings);
            foreach ($arrKeys as $idx => $fieldname) {
                $fieldname = preg_replace('/\.$/', '', $fieldname);
                $values[$step][$fieldname] = $this->parseValue($fieldname, $settings);
            }
            $this->globals->getSession()->set('values', $values);
        }
    }

    protected function parseValue($fieldname, $settings)
    {
        $value = null;
        //pre process the field value.
        if (is_array($settings[$fieldname . '.']['preProcessing.'])) {
            $settings[$fieldname . '.']['preProcessing.']['value'] = $value;
            $value = $this->utilityFuncs->getSingle($settings[$fieldname . '.'], 'preProcessing');
        }

        if ($value === null) {
            $mapping = $this->utilityFuncs->getSingle($settings[$fieldname . '.'], 'mapping');
            if (isset($this->data[$mapping])) {
                $value = $this->data[$mapping];
            } else {
                $value = $this->utilityFuncs->getSingle($settings, $fieldname);
            }
            if ($settings[$fieldname . '.']['separator']) {
                $separator = $settings[$fieldname . '.']['separator'];
                $value = GeneralUtility::trimExplode($separator, $value);
            }
        }

        //post process the field value.
        if (is_array($settings[$fieldname . '.']['postProcessing.'])) {
            $settings[$fieldname . '.']['postProcessing.']['value'] = $value;
            $value = $this->utilityFuncs->getSingle($settings[$fieldname . '.'], 'postProcessing');
        }

        if (isset($settings[$fieldname . '.']['type']) && $this->utilityFuncs->getSingle($settings[$fieldname . '.'], 'type') === 'upload') {
            if (!$this->files) {
                $this->files = [];
            }
            $this->files[$fieldname] = [];
            if (!empty($value)) {
                $uploadPath = $this->utilityFuncs->getTempUploadFolder($fieldname);
                $filesArray = $value;
                if (!is_array($filesArray)) {
                    $filesArray = GeneralUtility::trimExplode(',', $value);
                }

                foreach ($filesArray as $k => $uploadFile) {
                    if (strpos($uploadFile, '/') !== false) {
                        $file = Environment::getPublicPath() . '/' . $uploadFile;
                        $uploadedUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $uploadFile;
                    } else {
                        $file = Environment::getPublicPath() . '/' . $uploadPath . $uploadFile;
                        $uploadedUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $uploadPath . $uploadFile;
                    }

                    $uploadedUrl = str_replace('//', '/', $uploadedUrl);
                    $this->files[$fieldname][] = [
                        'name' => $uploadFile,
                        'uploaded_name' => $uploadFile,
                        'uploaded_path' => Environment::getPublicPath() . '/' . $uploadPath,
                        'uploaded_folder' => $uploadPath,
                        'uploaded_url' => $uploadedUrl,
                        'size' => filesize($file)
                    ];
                }
                $this->globals->getSession()->set('files', $this->files);
            }
        }
        return $value;
    }

    /**
     * Loads data from DB
     *
     * @return array of row data
     * @param array $settings
     */
    protected function loadDB($settings)
    {
        $table = $this->utilityFuncs->getSingle($settings, 'table');
        $sql = $this->getQuery($table, $settings);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $this->utilityFuncs->debugMessage($sql);
        $stmt = $connection->executeQuery($sql);

        $rows = $stmt->fetchAll();
        $rowCount = count($rows);
        if ($rowCount === 1) {
            return reset($rows);
        }
        if ($rowCount > 0) {
            $this->utilityFuncs->debugMessage('sql_too_many_rows', [$rowCount], 3);
        }
        return [];
    }

    /**
     * @param string $table
     * @param array $conf
     * @return string
     */
    protected function getQuery($table, $conf)
    {
        //map the old TypoScript setting "limit" to "begin" and "max".
        $limit = $this->utilityFuncs->getSingle($conf, 'limit');
        if (strlen($limit) > 0) {
            $parts = GeneralUtility::trimExplode(',', $limit);
            if (count($parts) === 2) {
                $conf['begin'] = $parts[0];
                $conf['max'] = $parts[1];
            } else {
                $conf['max'] = $parts[0];
            }
        }
        $sql = $this->globals->getCObj()->getQuery($table, $conf);

        // possible quotes: empty, ", ` or '
        $quotes = '|\"|\`|\'';
        //if pidInList is not set in TypoScript remove it from the where clause.
        if (!isset($conf['pidInList']) || strlen($conf['pidInList']) === 0) {
            $sql = preg_replace('/([^ ]+\.(' . $quotes . ')pid(' . $quotes . ') IN \([^ ]+\) AND )/i', '', $sql);
        }
        return $sql;
    }
}
