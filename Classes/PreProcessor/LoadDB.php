<?php
namespace Typoheads\Formhandler\PreProcessor;

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
     * @var Array $data as associative array. Row data from DB.
     * @access protected
     */
    protected $data;

    /**
     * @var Array $files as associative array.
     * @access protected
     */
    protected $files;

    /**
     * Main method called by the controller
     *
     * @return Array GP
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
     * @return void
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
     * @return void
     * @param Array $settings
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
                $value = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($separator, $value);
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
                    $filesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value);
                }

                foreach ($filesArray as $k => $uploadFile) {
                    if (strpos($uploadFile, '/') !== false) {
                        $file = PATH_site . $uploadFile;
                        $uploadedUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $uploadFile;
                    } else {
                        $file = PATH_site . $uploadPath . $uploadFile;
                        $uploadedUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $uploadPath . $uploadFile;
                    }

                    $uploadedUrl = str_replace('//', '/', $uploadedUrl);
                    $this->files[$fieldname][] = [
                        'name' => $uploadFile,
                        'uploaded_name' => $uploadFile,
                        'uploaded_path' => PATH_site . $uploadPath,
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
     * @return Array of row data
     * @param Array $settings
     * @param int $step
     */
    protected function loadDB($settings)
    {
        $store_lastBuiltQuery = $GLOBALS['TYPO3_DB']->store_lastBuiltQuery;
        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
        $res = $this->exec_getQuery($this->utilityFuncs->getSingle($settings, 'table'), $settings);
        $sql = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
        $this->utilityFuncs->debugMessage($sql);

        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = $store_lastBuiltQuery;
        $rowCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        if ($rowCount === 1) {
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            return $row;
        } elseif ($rowCount > 0) {
            $this->utilityFuncs->debugMessage('sql_too_many_rows', [$rowCount], 3);
        }
        return [];
    }

    /* (non-PHPdoc)
     * @see tslib_content::exec_getQuery
    */
    protected function exec_getQuery($table, $conf)
    {

        //map the old TypoScript setting "limit" to "begin" and "max".
        $limit = $this->utilityFuncs->getSingle($conf, 'limit');
        if (strlen($limit) > 0) {
            $parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $limit);
            if (count($parts) === 2) {
                $conf['begin'] = $parts[0];
                $conf['max'] = $parts[1];
            } else {
                $conf['max'] = $parts[0];
            }
        }
        $queryParts = $this->globals->getCObj()->getQuery($table, $conf, true);

        // possible quotes: empty, ", ` or '
        $quotes = '|\"|\`|\'';
        //if pidInList is not set in TypoScript remove it from the where clause.
        if (!isset($conf['pidInList']) || strlen($conf['pidInList']) === 0) {
            $queryParts['WHERE'] = preg_replace('/([^ ]+\.('.$quotes.')pid('.$quotes.') IN \([^ ]+\) AND )/i', '', $queryParts['WHERE']);
        }
        return $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
    }
}
