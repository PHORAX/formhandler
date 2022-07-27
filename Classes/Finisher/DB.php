<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
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
 * This finisher stores the submitted values into a table in the TYPO3 database according to the configuration.
 *
 * Example configuration:
 *
 * <code>
 * finishers.1.class = Finisher\DB
 *
 * #The table to store the records in
 * finishers.1.config.table = tt_content
 *
 * #The uid field. Default: uid
 * finishers.1.config.key = uid
 *
 * #Do not insert the record, but update an existing one.
 * #The uid of the existing record must exist in Get/Post
 * finishers.1.config.updateInsteadOfInsert = 1
 *
 * #map a form field to a db field.
 * finishers.1.config.fields.header.mapping = name
 *
 * #if form field is empty, insert this
 * finishers.1.config.fields.header.if_is_empty = None given
 * finishers.1.config.fields.bodytext.mapping = interests
 *
 * #if form field is an array, implode using this separator. Default: ,
 * finishers.1.config.fields.bodytext.separator = ,
 *
 * #add static values for some fields
 * finishers.1.config.fields.hidden = 1
 * finishers.1.config.fields.pid = 39
 *
 * #add special values
 * finishers.1.config.fields.subheader.special = sub_datetime
 * finishers.1.config.fields.crdate.special = sub_tstamp
 * finishers.1.config.fields.tstamp.special = sub_tstamp
 * finishers.1.config.fields.imagecaption.special = ip
 * </code>
 */
class DB extends AbstractFinisher {
  protected ?Connection $connection = null;

  /**
   * A flag to indicate if to insert the record or to update an existing one.
   */
  protected bool $doUpdate = false;

  /**
   * The field in the table holding the primary key.
   */
  protected string $key = '';

  /**
   * The name of the table to put the values into.
   */
  protected string $table = '';

  /**
   * Inits the finisher mapping settings values to internal attributes.
   */
  public function init(array $gp, array $settings): void {
    parent::init($gp, $settings);

    // set table
    $this->table = $this->utilityFuncs->getSingle($this->settings, 'table');
    if (!$this->table) {
      $this->utilityFuncs->throwException('no_table', '\\Typoheads\\Formhandler\\Finisher\\DB');

      return;
    }

    if (!isset($this->settings['fields.']) || !is_array($this->settings['fields.'])) {
      $this->utilityFuncs->throwException('no_fields', '\\Typoheads\\Formhandler\\Finisher\\DB');

      return;
    }

    // set primary key field
    $this->key = $this->utilityFuncs->getSingle($this->settings, 'key');
    if (0 === strlen($this->key)) {
      $this->key = 'uid';
    }

    // check whether to update or to insert a record
    $this->doUpdate = false;
    if (1 === (int) $this->utilityFuncs->getSingle($this->settings, 'updateInsteadOfInsert')) {
      // check if uid of record to update is in GP
      $uid = $this->getUpdateUid();

      $andWhere = $this->utilityFuncs->getSingle($this->settings, 'andWhere');
      $recordExists = $this->doesRecordExist($uid, $andWhere);
      if ($recordExists) {
        $this->doUpdate = true;
      } elseif (1 !== (int) $this->utilityFuncs->getSingle($this->settings, 'insertIfNoUpdatePossible')) {
        $this->utilityFuncs->debugMessage('no_update_possible', [], 2);
      }
    }
  }

  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    $this->utilityFuncs->debugMessage('data_stored');

    // set fields to insert/update
    $queryFields = $this->parseFields();

    // query the database
    $isSuccess = $this->save($queryFields);

    $saveDB = [];
    if (isset($this->gp['saveDB']) && is_array($this->gp['saveDB'])) {
      $saveDB = $this->gp['saveDB'];
    }

    // Store info in GP only if the query was successful
    if ($isSuccess) {
      // Get DB info, including UID
      if (!$this->doUpdate) {
        $this->gp['inserted_uid'] = $this->getInsertedUid();
        $this->gp[$this->table.'_inserted_uid'] = $this->gp['inserted_uid'];
        $info = [
          'table' => $this->table,
          'uid' => $this->gp['inserted_uid'],
          'uidField' => $this->key,
        ];
        array_push($saveDB, $info);
      } else {
        $uid = $this->getUpdateUid();
        $info = [
          'table' => $this->table,
          'uid' => $uid,
          'uidField' => $this->key,
        ];
        array_push($saveDB, $info);
      }

      // Insert the data written to DB into GP array
      $dataKeyName = $this->table;
      $dataKeyIndex = 1;
      while (isset($saveDB[$dataKeyName])) {
        ++$dataKeyIndex;
        $dataKeyName = $this->table.'_'.$dataKeyIndex;
      }
      $saveDB[$dataKeyName] = $queryFields;
    }

    $this->gp['saveDB'] = $saveDB;

    return $this->gp;
  }

  protected function doesRecordExist(int $uid, string $andWhere): bool {
    if (!$uid) {
      return false;
    }

    /** @var ConnectionPool $connectionPool */
    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

    $queryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
    $queryBuilder
      ->getRestrictions()
      ->removeAll()
    ;

    $queryBuilder
      ->select($this->key)
      ->from($this->table)
    ;

    $queryBuilder->where(
      $queryBuilder->expr()->eq($this->key, $queryBuilder->createNamedParameter($uid))
    );

    $andWhere = $this->utilityFuncs->prepareAndWhereString($andWhere);
    $andWhere = QueryHelper::stripLogicalOperatorPrefix($andWhere);
    if (!empty($andWhere)) {
      $queryBuilder->andWhere($andWhere);
    }

    $row = $queryBuilder->executeQuery()->fetchAssociative();
    if (is_array($row)) {
      return true;
    }

    return false;
  }

  /**
   * @param array<string, string> $queryFields
   */
  protected function doInsert(array $queryFields): bool {
    $queryBuilder = $this->getConnection()->createQueryBuilder();
    $queryBuilder->insert($this->table);

    $queryBuilder->values($queryFields);

    $query = $queryBuilder->getSQL();

    try {
      $stmt = $queryBuilder->execute();
    } catch (\Throwable $th) {
      $this->utilityFuncs->debugMessage('error', [$query], 3);

      return false;
    }

    return true;
  }

  /**
   * @param array<string, string> $queryFields
   */
  protected function doUpdate(int $uid, array $queryFields, string $andWhere): bool {
    /** @var ConnectionPool $connectionPool */
    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

    $queryBuilder = $connectionPool->getQueryBuilderForTable($this->table);
    $queryBuilder
      ->getRestrictions()
      ->removeAll()
    ;

    $queryBuilder->update($this->table);

    foreach ($queryFields as $k => $v) {
      $queryBuilder->set($k, $v);
    }

    $queryBuilder->where(
      $queryBuilder->expr()->eq($this->key, $queryBuilder->createNamedParameter($uid))
    );

    $andWhere = $this->utilityFuncs->prepareAndWhereString($andWhere);
    $andWhere = QueryHelper::stripLogicalOperatorPrefix($andWhere);
    if (!empty($andWhere)) {
      $queryBuilder->andWhere($andWhere);
    }

    $query = $queryBuilder->getSQL();

    try {
      $stmt = $queryBuilder->execute();
    } catch (\Throwable $th) {
      $this->utilityFuncs->debugMessage('error', [$query], 3);

      return false;
    }

    return true;
  }

  protected function getConnection(): Connection {
    if (null === $this->connection) {
      /** @var ConnectionPool $connectionPool */
      $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
      $this->connection = $connectionPool->getConnectionForTable($this->table);
    }

    return $this->connection;
  }

  /**
   * returns a list of uploaded files from given field.
   *
   * @param array<string, mixed> $files
   *
   * @return string list of filenames
   */
  protected function getFileList(array $files, string $fieldname): string {
    $filenames = [];
    $file = (array) ($files[$fieldname] ?? []);
    foreach ($file as $idx => $fileValues) {
      array_push($filenames, strval(((array) $fileValues)['uploaded_name'] ?? ''));
    }

    return implode(',', $filenames);
  }

  /**
   * Returns the last inserted UID.
   *
   * @return int UID
   */
  protected function getInsertedUid(): int {
    return (int) $this->getConnection()->lastInsertId();
  }

  /**
   * Returns current UID to use for updating the DB.
   *
   * @return int UID
   */
  protected function getUpdateUid(): int {
    $uid = intval($this->utilityFuncs->getSingle($this->settings, 'key_value'));
    $disableFallback = (1 == intval($this->utilityFuncs->getSingle($this->settings, 'disableUpdateUidFallback')));
    if (!$disableFallback) {
      if (!$uid) {
        $uid = intval($this->gp[$this->key] ?? 0);
      }
      if (!$uid) {
        $uid = intval($this->gp['inserted_uid']);
      }
    }

    return $uid;
  }

  /**
   * Parses mapping settings and builds an array holding the query fields information.
   *
   * @return array<string, string> The query fields
   */
  protected function parseFields(): array {
    $queryFields = [];

    $fields = (array) ($this->settings['fields.'] ?? []);
    // parse mapping
    /** @var string $fieldname */
    foreach ($fields as $fieldname => $options) {
      $fieldname = str_replace('.', '', $fieldname);
      $fieldValue = '';
      if (is_array($options)) {
        if (!isset($options['special'])) {
          $mapping = strval($options['mapping'] ?? '');

          // if no mapping default to the name of the form field
          if (empty($mapping)) {
            $mapping = $fieldname;
          }

          $fieldValue = $this->utilityFuncs->getGlobal($mapping, $this->gp);

          // pre process the field value. e.g. to format a date
          if (isset($options['preProcessing.']) && is_array($options['preProcessing.'])) {
            if (!isset($options['preProcessing.']['value'])) {
              $options['preProcessing.']['value'] = $fieldValue;
            }
            $fieldValue = $this->utilityFuncs->getSingle($options, 'preProcessing');
          }

          if (isset($options['mapping.']) && is_array($options['mapping.'])) {
            if (!isset($options['mapping.']['value'])) {
              $options['mapping.']['value'] = $fieldValue;
            }
            $fieldValue = $this->utilityFuncs->getSingle($options, 'mapping');
          }

          // process empty value handling
          if (isset($options['ifIsEmpty']) && 0 === strlen(strval($fieldValue))) {
            $fieldValue = $this->utilityFuncs->getSingle($options, 'ifIsEmpty');
          }

          if (1 == intval($this->utilityFuncs->getSingle($options, 'zeroIfEmpty')) && 0 === strlen(strval($fieldValue))) {
            $fieldValue = 0;
          }

          // process array handling
          if (is_array($fieldValue)) {
            $separator = ',';
            if ($options['separator']) {
              $separator = $this->utilityFuncs->getSingle($options, 'separator');
            }
            $fieldValue = implode($separator, $fieldValue);
          }

          // process uploaded files
          $files = (array) ($this->globals->getSession()?->get('files') ?? []);
          if (isset($files[$fieldname]) && is_array($files[$fieldname])) {
            $fieldValue = $this->getFileList($files, $fieldname);
          }
        } else {
          switch ($options['special']) {
            case 'saltedpassword':
              $field = $this->utilityFuncs->getSingle($options['special.'], 'field');

              /** @var PasswordHashFactory $passwordHashFactory */
              $passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
              $hashInstance = $passwordHashFactory->getDefaultHashInstance('FE');
              $encryptedPassword = $hashInstance->getHashedPassword(strval($this->gp[$field] ?? ''));

              $fieldValue = $encryptedPassword;

              break;

            case 'files':
              $field = $this->utilityFuncs->getSingle($options['special.'], 'field');
              if (isset($options['special.']['separator'])) {
                $separator = $this->utilityFuncs->getSingle($options['special.'], 'separator');
              } else {
                $separator = ',';
              }

              $filesArray = [];
              if (isset($options['special.']['info'])) {
                $info = strval($this->utilityFuncs->getSingle($options['special.'], 'info'));
              } else {
                $info = '[uploaded_name]';
              }
              $files = (array) ($this->globals->getSession()?->get('files') ?? []);
              if (isset($files[$field]) && is_array($files[$field])) {
                foreach ($files[$field] as $file) {
                  $infoString = $info;
                  foreach ($file as $infoKey => $infoValue) {
                    $infoString = str_replace('['.$infoKey.']', $infoValue, $infoString);
                  }
                  $filesArray[] = $infoString;
                }
              }
              if (isset($options['special.']['index'])) {
                $index = intval($this->utilityFuncs->getSingle($options['special.'], 'index'));
                if (isset($filesArray[$index])) {
                  $fieldValue = $filesArray[$index];
                }
              } else {
                $fieldValue = implode($separator, $filesArray);
              }

              break;

            case 'date':
              $field = $this->utilityFuncs->getSingle($options['special.'], 'field');
              $date = strval($this->gp[$field]);
              $dateFormat = 'Y-m-d';
              if ($options['special.']['dateFormat']) {
                $dateFormat = $this->utilityFuncs->getSingle($options['special.'], 'dateFormat');
              } elseif ($options['special.']['format']) {
                $dateFormat = $this->utilityFuncs->getSingle($options['special.'], 'format');
              }
              $fieldValue = $this->utilityFuncs->dateToTimestamp($date, $dateFormat);

              break;

            case 'datetime':
              if (version_compare(PHP_VERSION, '5.3.0') < 0) {
                $this->utilityFuncs->throwException('error_datetime');
              }
              $field = $this->utilityFuncs->getSingle($options['special.'], 'field');
              $date = strval($this->gp[$field]);
              $dateFormat = 'Y-m-d H:i:s';
              if ($options['special.']['dateFormat']) {
                $dateFormat = $this->utilityFuncs->getSingle($options['special.'], 'dateFormat');
              } elseif ($options['special.']['format']) {
                $dateFormat = $this->utilityFuncs->getSingle($options['special.'], 'format');
              }
              $fieldValue = $this->utilityFuncs->dateToTimestamp($date, $dateFormat);

              break;

            case 'sub_datetime':
              $dateFormat = 'Y-m-d H:i:s';
              if ($options['special.']['dateFormat']) {
                $dateFormat = $this->utilityFuncs->getSingle($options['special.'], 'dateFormat');
              } elseif ($options['special.']['format']) {
                $dateFormat = $this->utilityFuncs->getSingle($options['special.'], 'format');
              }
              $fieldValue = date($dateFormat, time());

              break;

            case 'sub_tstamp':
              $fieldValue = time();

              break;

            case 'ip':
              $fieldValue = GeneralUtility::getIndpEnv('REMOTE_ADDR');

              break;

            case 'inserted_uid':
              $table = $this->utilityFuncs->getSingle($options['special.'], 'table');
              if (isset($this->gp['saveDB']) && is_array($this->gp['saveDB'])) {
                foreach ($this->gp['saveDB'] as $idx => $info) {
                  if ($info['table'] === $table) {
                    $fieldValue = $info['uid'];
                  }
                }
              }

              break;
          }
        }
      } else {
        $fieldValue = $options;
      }

      // post process the field value after formhandler did it's magic.
      if (is_array($options) && isset($options['postProcessing.']) && is_array($options['postProcessing.'])) {
        if (!isset($options['postProcessing.']['value'])) {
          $options['postProcessing.']['value'] = $fieldValue;
        }
        $fieldValue = $this->utilityFuncs->getSingle($options, 'postProcessing');
      }

      $queryFields[$fieldname] = $fieldValue;

      if (is_array($options) && 1 == intval($this->utilityFuncs->getSingle($options, 'nullIfEmpty')) && 0 == strlen(strval($queryFields[$fieldname] ?? ''))) {
        unset($queryFields[$fieldname]);
      }
    }

    return $queryFields;
  }

  /**
   * Method to query the database making an insert or update statement using the given fields.
   *
   * @param array<string, string> &$queryFields Array holding the query fields
   *
   * @return bool Success flag
   */
  protected function save(array &$queryFields): bool {
    // insert
    if (!$this->doUpdate) {
      $isSuccess = $this->doInsert($queryFields);
    }
    // update
    else {
      // check if uid of record to update is in GP
      $uid = $this->getUpdateUid();

      $andWhere = $this->utilityFuncs->getSingle($this->settings, 'andWhere');
      $isSuccess = $this->doUpdate($uid, $queryFields, $andWhere);
    }

    return $isSuccess;
  }
}
