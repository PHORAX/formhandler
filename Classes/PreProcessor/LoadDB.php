<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\PreProcessor;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This script is part of the TYPO3 project - inspiring people to share!
 *
 * TYPO3 is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by
 * the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 */

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
 * @author    Mathias Bolt Lesniak, LiliO Design <mathias@lilio.com>
 */
class LoadDB extends AbstractPreProcessor {
  /**
   * @var array<string, mixed> as associative array. Row data from DB.
   */
  protected array $data;

  /**
   * @var array<string, mixed> as associative array
   */
  protected array $files;

  /**
   * Main method called by the controller.
   *
   * @return array<string, mixed> GP
   */
  public function process(): array {
    $this->data = $this->loadDB($this->settings['select.']);

    foreach ($this->settings as $step => $stepSettings) {
      $step = (int) preg_replace('/\.$/', '', $step);

      if ('select' !== $step) {
        if (1 === (int) $step && !is_string($stepSettings)) {
          $this->loadDBToGP($stepSettings);
        } elseif (is_numeric($step) && !is_string($stepSettings)) {
          $this->loadDBToSession($stepSettings, $step);
        }
      }
    }

    return $this->gp;
  }

  /**
   * @param array<string, mixed> $conf
   */
  protected function getQuery(string $table, array $conf): string {
    // map the old TypoScript setting "limit" to "begin" and "max".
    $limit = $this->utilityFuncs->getSingle($conf, 'limit');
    if (strlen($limit) > 0) {
      $parts = GeneralUtility::trimExplode(',', $limit);
      if (2 === count($parts)) {
        $conf['begin'] = $parts[0];
        $conf['max'] = $parts[1];
      } else {
        $conf['max'] = $parts[0];
      }
    }
    $sql = $this->globals->getCObj()->getQuery($table, $conf);

    // possible quotes: empty, ", ` or '
    $quotes = '|\"|\`|\'';
    // if pidInList is not set in TypoScript remove it from the where clause.
    if (!isset($conf['pidInList']) || 0 === strlen($conf['pidInList'])) {
      $sql = preg_replace('/([^ ]+\.('.$quotes.')pid('.$quotes.') IN \([^ ]+\) AND )/i', '', $sql);
    }

    return $sql;
  }

  /**
   * Loads data from DB.
   *
   * @param array<string, mixed> $settings
   *
   * @return array<string, mixed> of row data
   */
  protected function loadDB(array $settings): array {
    $table = $this->utilityFuncs->getSingle($settings, 'table');
    $sql = $this->getQuery($table, $settings);

    /** @var ConnectionPool $connectionPool */
    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

    $connection = $connectionPool->getConnectionForTable($table);
    $this->utilityFuncs->debugMessage($sql);
    $stmt = $connection->executeQuery($sql);

    $rows = $stmt->fetchAllAssociative();
    $rowCount = count($rows);
    if (1 === $rowCount) {
      return $rows[0];
    }
    if ($rowCount > 0) {
      $this->utilityFuncs->debugMessage('sql_too_many_rows', [$rowCount], 3);
    }

    return [];
  }

  /**
   * Loads data from DB intto the GP Array.
   *
   * @param array<string, mixed> $settings
   */
  protected function loadDBToGP(array $settings): void {
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
   * @param array<string, mixed> $settings
   */
  protected function loadDBToSession(array $settings, int $step): void {
    session_start();
    if (is_array($settings) && $step) {
      $values = (array) $this->globals->getSession()->get('values');
      $arrKeys = array_keys($settings);
      foreach ($arrKeys as $idx => $fieldname) {
        $fieldname = preg_replace('/\.$/', '', $fieldname);
        $values[$step][$fieldname] = $this->parseValue($fieldname, $settings);
      }
      $this->globals->getSession()->set('values', $values);
    }
  }

  /**
   * @param array<string, mixed> $settings
   */
  protected function parseValue(string $fieldname, array $settings): string {
    $value = null;
    // pre process the field value.
    if (is_array($settings[$fieldname.'.']['preProcessing.'])) {
      $settings[$fieldname.'.']['preProcessing.']['value'] = $value;
      $value = $this->utilityFuncs->getSingle($settings[$fieldname.'.'], 'preProcessing');
    }

    if (null === $value) {
      $mapping = $this->utilityFuncs->getSingle($settings[$fieldname.'.'], 'mapping');
      if (isset($this->data[$mapping])) {
        $value = $this->data[$mapping];
      } else {
        $value = $this->utilityFuncs->getSingle($settings, $fieldname);
      }
      if ($settings[$fieldname.'.']['separator']) {
        $separator = $settings[$fieldname.'.']['separator'];
        $value = GeneralUtility::trimExplode($separator, $value);
      }
    }

    // post process the field value.
    if (is_array($settings[$fieldname.'.']['postProcessing.'])) {
      $settings[$fieldname.'.']['postProcessing.']['value'] = $value;
      $value = $this->utilityFuncs->getSingle($settings[$fieldname.'.'], 'postProcessing');
    }

    if (isset($settings[$fieldname.'.']['type']) && 'upload' === $this->utilityFuncs->getSingle($settings[$fieldname.'.'], 'type')) {
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
          if (false !== strpos($uploadFile, '/')) {
            $file = Environment::getPublicPath().'/'.$uploadFile;
            $uploadedUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$uploadFile;
          } else {
            $file = Environment::getPublicPath().'/'.$uploadPath.$uploadFile;
            $uploadedUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$uploadPath.$uploadFile;
          }

          $uploadedUrl = str_replace('//', '/', $uploadedUrl);
          $this->files[$fieldname][] = [
            'name' => $uploadFile,
            'uploaded_name' => $uploadFile,
            'uploaded_path' => Environment::getPublicPath().'/'.$uploadPath,
            'uploaded_folder' => $uploadPath,
            'uploaded_url' => $uploadedUrl,
            'size' => filesize($file),
          ];
        }
        $this->globals->getSession()->set('files', $this->files);
      }
    }

    return $value;
  }
}
