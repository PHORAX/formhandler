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
 * When a BE-user is logged in and autoCreate is to true this looks if
 * the specified table exists and if not creates it with the key-field (uid).
 *
 * Furthermore it will map all form fields that are'nt submits to DB-fields,
 * expecting the field names are the same. You can avoid creation of certain
 * fields by setting those form fields as comma separated list in .excludeFields
 *
 * Only generates the table when .autoCreate is to true and a BE-user is logged
 * in - so be sure to do that.
 *
 * @author    Christian Opitz <co@netzelf.de>
 */
class AutoDB extends DB
{

    /**
     * The name of the table to put the values into.
     * @todo Make it protected var in Tx_Formhandler_AbstractFinisher
     * @var string
     */
    public $settings;

    /**
     * @var TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $db;

    /**
     * @var string Attributes for new db fields
     */
    protected $newFieldsSqlAttribs = 'TINYTEXT NOT NULL';

    /**
     * Initialize the component
     *
     * @param array $gp
     * @param array $settings
     */
    public function init($gp, $settings)
    {
        if (!is_array($settings['fields.'])) {
            $settings['fields.'] = [];
        }
        $this->settings = $settings;
        parent::init($gp, $settings);

        if ($this->settings['newFieldsSqlAttribs']) {
            $this->newFieldsSqlAttribs = $this->utilityFuncs->getSingle($this->settings, 'newFieldsSqlAttribs');
        }

        $this->db = $GLOBALS['TYPO3_DB'];
    }

    /* (non-PHPdoc)
     * @see Classes/Finisher/Tx_Formhandler_Finisher_DB#parseFields()
     */
    protected function parseFields()
    {
        $doAutoCreate = intval($this->utilityFuncs->getSingle($this->settings, 'newFieldsSqlAttribs'));
        if ($doAutoCreate === 1 && $GLOBALS['TSFE']->beUserLogin) {
            $this->createTable();
        }

        $dbFields = $this->db->admin_get_fields($this->table);

        foreach ($dbFields as $field => $properties) {
            if ($field != $this->key && !isset($this->settings['fields.'][$field])) {
                $this->settings['fields.'][$field . '.'] = ['mapping' => $field];
            }
        }

        $fields = parent::parseFields();
        $escapedFields = [];
        foreach ($fields as $field => $value) {
            $escapedFields['`' . $field . '`'] = $value;
        }
        return $escapedFields;
    }

    /**
     * Retrieve the fieldnames registered by the fluid form (those include
     * the prefix if set)
     *
     * @return array
     */
    protected function getFormFieldNames()
    {
        $pattern = '/\<(?=input|select|textarea)[^\>]*name=("|\')([^"\']*)\1/i';

        $templateFile = $this->globals->getTemplateCode();
        preg_match_all($pattern, $templateFile, $matches);

        return (array)$matches[2];
    }

    /**
     * Gets the top level fields from the formFieldNames (@see getFormFieldNames)
     *
     * @return array
     */
    protected function getFormFields()
    {
        $invokePrefix = strlen($this->globals->getFormValuesPrefix()) > 0;
        $prefix = $this->globals->getFormValuesPrefix();
        $fields = [];

        foreach ($this->getFormFieldNames() as $fieldName) {
            $keys = explode('[', str_replace(']', '', $fieldName));
            if ($invokePrefix && (($keys[0] == $prefix) || ($keys[0] == '###formValuesPrefix###')) && !empty($keys[1])) {
                $fields[$keys[1]] = $keys[1];
            } elseif (!$invokePrefix && strlen($keys[0])) {
                $fields[$keys[0]] = $keys[0];
            }
        }

        return $fields;
    }

    /**
     * Looks if the specified table exists and if not create it with the key-
     * field (uid). Then it syncs the DB-fields with the fields found in the form
     * with help of template parser
     */
    protected function createTable()
    {
        $fields = $this->getFormFields();
        $excludeFields = trim($this->utilityFuncs->getSingle($this->settings, 'excludeFields'));
        if (strlen($excludeFields) > 0) {
            $excludes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $excludeFields);
            foreach ($excludes as $exclude) {
                unset($fields[$exclude]);
            }
        }

        $globalSettings = $this->globals->getSettings();
        $isDebugMode = $this->utilityFuncs->getSingle($globalSettings, 'debug');
        if (intval($isDebugMode) === 1) {
            $this->db->debugOutput = 1;
        }

        $res = $this->db->sql_query("SHOW TABLES LIKE '" . $this->table . "'");

        if (!$this->db->sql_num_rows($res)) {
            $query = "CREATE TABLE `" . $this->table . "` (
				`" . $this->key . "` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
			)";
            $this->db->sql_query($query);
            $this->utilityFuncs->debugMessage('sql_request', [$query]);
            $dbFields = [$this->key];
        } else {
            $dbFields = array_keys($this->db->admin_get_fields($this->table));
        }
        $this->db->sql_free_result($res);

        $createFields = array_diff($fields, $dbFields);

        if (count($createFields)) {
            $sql = 'ALTER TABLE ' . $this->table . ' ADD `';
            $sql .= implode('` ' . $this->newFieldsSqlAttribs . ', ADD `', $createFields);
            $sql .= '` ' . $this->newFieldsSqlAttribs;

            $this->db->sql_query($sql);
            $this->utilityFuncs->debugMessage('sql_request', [$sql]);
            if ($this->db->sql_error()) {
                $this->utilityFuncs->debugMessage('error', [$this->db->sql_error()], 3);
            }
        }
    }
}
