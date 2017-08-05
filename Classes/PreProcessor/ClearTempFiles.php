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
class ClearTempFiles extends AbstractPreProcessor
{

    /**
     * The main method called by the controller
     *
     * @param array $gp The GET/POST parameters
     * @param array $settings The defined TypoScript settings for the finisher
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        $olderThanValue = $this->utilityFuncs->getSingle($this->settings['clearTempFilesOlderThan.'], 'value');
        $olderThanUnit = $this->utilityFuncs->getSingle($this->settings['clearTempFilesOlderThan.'], 'unit');
        if (strlen($olderThanValue) > 0 && is_numeric($olderThanValue)) {
            $this->clearTempFiles($olderThanValue, $olderThanUnit);
        }
        return $this->gp;
    }

    /**
     * Deletes all files older than a specific time in a temporary upload folder.
     * Settings for the threshold time and the folder are made in TypoScript.
     *
     * @param integer $olderThanValue Delete files older than this value.
     * @param string $olderThanUnit The unit for $olderThan. May be seconds|minutes|hours|days
     * @return void
     */
    protected function clearTempFiles($olderThanValue, $olderThanUnit)
    {
        if (!$olderThanValue) {
            return;
        }

        $uploadFolders = $this->utilityFuncs->getAllTempUploadFolders();

        foreach ($uploadFolders as $uploadFolder) {

            //build absolute path to upload folder
            $path = $this->utilityFuncs->getDocumentRoot() . $uploadFolder;
            $path = $this->utilityFuncs->sanitizePath($path);

            //read files in directory
            $tmpFiles = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($path);

            $this->utilityFuncs->debugMessage('cleaning_temp_files', [$path]);

            //calculate threshold timestamp
            $threshold = $this->utilityFuncs->getTimestamp($olderThanValue, $olderThanUnit);

            //for all files in temp upload folder
            foreach ($tmpFiles as $idx => $file) {

                //if creation timestamp is lower than threshold timestamp
                //delete the file
                $creationTime = filemtime($path . $file);

                //fix for different timezones
                $creationTime += date('O') / 100 * 60;

                if ($creationTime < $threshold) {
                    unlink($path . $file);
                    $this->utilityFuncs->debugMessage('deleting_file', [$file]);
                }
            }
        }
    }
}
