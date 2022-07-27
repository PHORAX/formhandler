<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

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
 * This finisher stores uploaded files by a user to a final folder. At the time this finisher is called, it is assured, that the form was fully submitted and valid.
 * Use this finisher to move the uploaded files to a save folder where they are not cleared by a possibly time based deletion.
 * This class needs a parameter "finishedUploadFolder" to be set in TS.
 *
 * Sample configuration:
 *
 * <code>
 * finishers.1.class = Tx_Formhandler_Finisher_StoreUploadedFiles
 * finishers.1.config.finishedUploadFolder = uploads/formhandler/finished/
 * finishers.1.config.renameScheme = [pid]_[filename]_[md5]_[time]_[marker1]_[marker2]
 * finishers.1.config.schemeMarkers.marker1 = Value
 * finishers.1.config.schemeMarkers.marker2 = TEXT
 * finishers.1.config.schemeMarkers.marker2.value = Textvalue
 * </code>
 */
class StoreUploadedFiles extends AbstractFinisher {
  /**
   * The main method called by the controller.
   */
  public function process(mixed &$error = null): array|string {
    if (isset($this->settings['finishedUploadFolder']) || (isset($this->settings['finishedUploadFolder.']) && is_array($this->settings['finishedUploadFolder.']))) {
      // move the uploaded files
      $this->moveUploadedFiles();
    }

    return $this->gp;
  }

  /**
   * Generates a new filename for an uploaded file using settings in TypoScript.
   *
   * @param string $oldName The current filename
   * @param string $field   The current field
   *
   * @return string The new filename
   */
  protected function getNewFilename(string $oldName, string $field): string {
    $fileparts = explode('.', $oldName);
    $fileext = '.'.$fileparts[count($fileparts) - 1];
    array_pop($fileparts);
    $filename = implode('.', $fileparts);

    $namingScheme = $this->utilityFuncs->getSingle($this->settings, 'renameScheme');
    if (!$namingScheme) {
      $namingScheme = '[filename]_[time]';
    }
    $newFilename = $namingScheme;
    $newFilename = str_replace('[filename]', $filename, $newFilename);
    $newFilename = str_replace('[field]', $field, $newFilename);
    $newFilename = str_replace('[time]', (string) time(), $newFilename);
    $newFilename = str_replace('[md5]', md5($filename), $newFilename);
    $newFilename = str_replace('[pid]', $GLOBALS['TSFE']->id, $newFilename);
    $newFilename = $this->replaceSchemeMarkers($newFilename);

    // remove ',' from filename, would be handled as file separator
    $newFilename = str_replace(',', '', $newFilename);
    $newFilename = $this->utilityFuncs->doFileNameReplace($newFilename);
    $newFilename .= $fileext;

    return $newFilename;
  }

  /**
   * Builds the path to the final upload folder depending on the current field processed.
   *
   * @param string $field The current field name
   *
   * @return string The new path
   */
  protected function getNewFolderPath(string $field): string {
    if (isset($this->settings['finishedUploadFolder.']) && is_array($this->settings['finishedUploadFolder.']) && isset($this->settings['finishedUploadFolder.'][$field])) {
      $newFolder = $this->utilityFuncs->getSingle($this->settings['finishedUploadFolder.'], $field);
    } else {
      $newFolder = $this->utilityFuncs->getSingle($this->settings, 'finishedUploadFolder');
    }
    $newFolder = $this->utilityFuncs->sanitizePath($newFolder);
    $newFolder = $this->replaceSchemeMarkers($newFolder);
    $uploadPath = $this->utilityFuncs->getDocumentRoot().$newFolder;
    $uploadPath = $this->utilityFuncs->sanitizePath($uploadPath);
    if (!file_exists($uploadPath)) {
      $doCreateNonExistingFolder = (int) $this->utilityFuncs->getSingle($this->settings, 'createNonExistingFolder');
      if (!isset($this->settings['createNonExistingFolder'])) {
        $doCreateNonExistingFolder = 1;
      }
      if (1 === $doCreateNonExistingFolder) {
        GeneralUtility::mkdir_deep($this->utilityFuncs->getDocumentRoot().$newFolder);
        $this->utilityFuncs->debugMessage('Creating directory "'.$newFolder.'"');
      } else {
        $this->utilityFuncs->throwException('Directory "'.$newFolder.'" doesn\'t exist!');
      }
    }

    return $uploadPath;
  }

  /**
   * Moves uploaded files from temporary upload folder to a specified new folder.
   * This enables you to move the files from a successful submission to another folder and clean the files in temporary upload folder from time to time.
   *
   * TypoScript example:
   *
   * 1. Set the temporary upload folder
   * <code>
   * plugin.tx_formhandler_pi1.settings.files.tmpUploadFolder = uploads/formhandler/tmp
   * </code>
   *
   * 2. Set the folder to move the files to after submission
   * <code>
   * plugin.tx_formhandler_pi1.settings.finishers.1.class = Tx_Formhandler_Finisher_StoreUploadedFiles
   * plugin.tx_formhandler_pi1.settings.finishers.1.config.finishedUploadFolder = uploads/formhandler/finishedFiles/
   * plugin.tx_formhandler_pi1.settings.finishers.1.config.renameScheme = [filename]_[md5]_[time]
   * </code>
   */
  protected function moveUploadedFiles(): void {
    $sessionFiles = $this->globals->getSession()?->get('files') ?? '';
    if (is_array($sessionFiles) && !empty($sessionFiles)) {
      $disablePathCheck = (int) $this->utilityFuncs->getSingle($this->settings, 'disablePathCheck');
      foreach ($sessionFiles as $field => $files) {
        $this->gp[$field] = [];
        $uploadPath = $this->getNewFolderPath($field);
        if (strlen($uploadPath) > 0) {
          foreach ($files as $key => $file) {
            if ($file['uploaded_path'] !== $uploadPath || 1 === $disablePathCheck) {
              $newFilename = $this->getNewFilename($file['uploaded_name'], $field);
              $filename = substr($newFilename, 0, strrpos($newFilename, '.') ?: null);
              $ext = substr($newFilename, strrpos($newFilename, '.') ?: (strlen($newFilename) - 1));

              $suffix = 1;

              // rename if exists
              while (file_exists($uploadPath.$newFilename)) {
                $newFilename = $filename.'_'.$suffix.$ext;
                ++$suffix;
              }

              $this->utilityFuncs->debugMessage(
                'copy_file',
                [
                  $file['uploaded_path'].$file['uploaded_name'],
                  $uploadPath.$newFilename,
                ]
              );
              copy($file['uploaded_path'].$file['uploaded_name'], $uploadPath.$newFilename);
              GeneralUtility::fixPermissions($uploadPath.$newFilename);
              unlink($file['uploaded_path'].$file['uploaded_name']);
              $newFolder = str_replace($this->utilityFuncs->getDocumentRoot(), '', $uploadPath);
              $sessionFiles[$field][$key]['uploaded_path'] = $uploadPath;
              $sessionFiles[$field][$key]['uploaded_name'] = $newFilename;
              $sessionFiles[$field][$key]['uploaded_folder'] = $newFolder;
              $sessionFiles[$field][$key]['uploaded_url'] = strval(GeneralUtility::getIndpEnv('TYPO3_SITE_URL')).$newFolder.$newFilename;
              if (!isset($this->gp[$field]) || !is_array($this->gp[$field])) {
                $this->gp[$field] = [];
              }
              array_push($this->gp[$field], $newFilename);
            } else {
              array_push($this->gp[$field], $file['uploaded_name']);
            }
          }
        }
      }
      $this->globals->getSession()?->set('files', $sessionFiles);
    }
  }

  protected function replaceSchemeMarkers(string $str): string {
    $replacedStr = $str;
    if (isset($this->settings['schemeMarkers.']) && is_array($this->settings['schemeMarkers.'])) {
      foreach ($this->settings['schemeMarkers.'] as $markerName => $options) {
        if (!(strpos($markerName, '.') > 0)) {
          $value = $options;

          // use field value
          if (isset($this->settings['schemeMarkers.'][$markerName.'.']) && !strcmp($options, 'fieldValue')) {
            $value = $this->gp[$this->settings['schemeMarkers.'][$markerName.'.']['field']];
            if (is_array($value)) {
              $separator = $this->utilityFuncs->getSingle($this->settings['schemeMarkers.'][$markerName.'.'], 'separator');
              if (0 === strlen($separator)) {
                $separator = '-';
              }
              $value = implode($separator, $value);
            }
          } elseif (isset($this->settings['schemeMarkers.'][$markerName.'.'])) {
            $value = $this->utilityFuncs->getSingle($this->settings['schemeMarkers.'], $markerName);
          }
          $replacedStr = str_replace('['.$markerName.']', $value, $replacedStr);
        }
      }
    }

    return $replacedStr;
  }
}
