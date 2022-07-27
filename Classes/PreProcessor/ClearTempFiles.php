<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\PreProcessor;

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
 * A pre processor cleaning old files in the temporary upload folder if set.
 *
 * Example:
 * <code>
 * preProcessors.1.class = Tx_Formhandler_PreProcessor_ClearTempFiles
 *
 * preProcessors.1.config.clearTempFilesOlderThan.value = 17
 * preProcessors.1.config.clearTempFilesOlderThan.unit = hours
 * </code>
 */
class ClearTempFiles extends AbstractPreProcessor {
  /**
   * The main method called by the controller.
   *
   * @return array<string, mixed> The probably modified GET/POST parameters
   */
  public function process(mixed &$error = null): array {
    $olderThanValue = intval($this->utilityFuncs->getSingle((array) ($this->settings['clearTempFilesOlderThan.'] ?? []), 'value'));
    $olderThanUnit = $this->utilityFuncs->getSingle((array) ($this->settings['clearTempFilesOlderThan.'] ?? []), 'unit');
    $this->clearTempFiles($olderThanValue, $olderThanUnit);

    return $this->gp;
  }

  /**
   * Deletes all files older than a specific time in a temporary upload folder.
   * Settings for the threshold time and the folder are made in TypoScript.
   *
   * @param int    $olderThanValue delete files older than this value
   * @param string $olderThanUnit  The unit for $olderThan. May be seconds|minutes|hours|days
   */
  protected function clearTempFiles(int $olderThanValue, string $olderThanUnit): void {
    if (!$olderThanValue) {
      return;
    }

    $uploadFolders = $this->utilityFuncs->getAllTempUploadFolders();

    foreach ($uploadFolders as $uploadFolder) {
      // build absolute path to upload folder
      $path = $this->utilityFuncs->getDocumentRoot().$uploadFolder;
      $path = $this->utilityFuncs->sanitizePath($path);

      // read files in directory
      $tmpFiles = GeneralUtility::getFilesInDir($path);

      $this->utilityFuncs->debugMessage('cleaning_temp_files', [$path]);

      if (!is_array($tmpFiles)) {
        return;
      }

      // calculate threshold timestamp
      $threshold = $this->utilityFuncs->getTimestamp($olderThanValue, $olderThanUnit);

      // for all files in temp upload folder
      foreach ($tmpFiles as $idx => $file) {
        // if creation timestamp is lower than threshold timestamp
        // delete the file
        $creationTime = filemtime($path.$file);

        // fix for different timezones
        $creationTime += date('O') / 100 * 60;

        if ($creationTime < $threshold) {
          unlink($path.$file);
          $this->utilityFuncs->debugMessage('deleting_file', [$file]);
        }
      }
    }
  }
}
