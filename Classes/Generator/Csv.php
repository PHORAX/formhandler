<?php
namespace Typoheads\Formhandler\Generator;

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
 * CSV generator class for Formhandler
 */
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Resources/PHP/parsecsv.lib.php');

class Csv extends AbstractGenerator
{

    /**
     * Renders the CSV.
     *
     * @return mixed
     */
    public function process()
    {
        $params = $this->gp;
        $exportParams = $this->utilityFuncs->getSingle($this->settings, 'exportParams');
        if (!is_array($exportParams) && strpos($exportParams, ',') !== false) {
            $exportParams = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $exportParams);
        }

        //build data
        foreach ($params as $key => &$value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            if (!empty($exportParams) && !in_array($key, $exportParams)) {
                unset($params[$key]);
            }
            $value = str_replace('"', '""', $value);
        }

        // create new parseCSV object.
        $csv = new \parseCSV();

        //parseCSV expects data to be a two dimensional array
        $data = [$params];

        $fields = false;
        if (intval($this->utilityFuncs->getSingle($this->settings, 'addFieldNames')) === 1) {
            $fields = array_keys($params);
            $csv->heading = true;
        }

        if ($this->settings['delimiter']) {
            $csv->delimiter = $csv->output_delimiter = $this->utilityFuncs->getSingle($this->settings, 'delimiter');
        }
        if ($this->settings['enclosure']) {
            $csv->enclosure = $this->utilityFuncs->getSingle($this->settings, 'enclosure');
        }
        $inputEncoding = $this->utilityFuncs->getSingle($this->settings, 'inputEncoding');
        if (strlen(trim($inputEncoding)) === 0) {
            $inputEncoding = 'utf-8';
        }
        $outputEncoding = $this->utilityFuncs->getSingle($this->settings, 'outputEncoding');
        if (strlen(trim($outputEncoding)) === 0) {
            $outputEncoding = 'utf-8';
        }
        $csv->input_encoding = strtolower($inputEncoding);
        $csv->output_encoding = strtolower($outputEncoding);

        $csv->convert_encoding = false;
        if ($csv->input_encoding !== $csv->output_encoding) {
            $csv->convert_encoding = true;
        }
        if (intval($this->settings['returnFileName']) === 1 || intval($this->settings['returnGP']) === 1) {
            $outputPath = $this->utilityFuncs->getDocumentRoot();
            if ($this->settings['customTempOutputPath']) {
                $outputPath .= $this->settings['customTempOutputPath'];
            } else {
                $outputPath .= '/typo3temp/';
            }
            $outputPath = $this->utilityFuncs->sanitizePath($outputPath);
            $filename = $outputPath . $this->settings['filePrefix'] . $this->utilityFuncs->generateHash() . '.csv';
            $csv->save($filename, $data, false, $fields);
            if (intval($this->settings['returnFileName']) === 1) {
                return $filename;
            } else {
                if (!is_array($this->gp['generator-csv-generated-files'])) {
                    $this->gp['generator-csv-generated-files'] = [];
                }
                $this->gp['generator-csv-generated-files'][] = $filename;
                return $this->gp;
            }
        } else {
            $fileName = 'formhandler.csv';
            if ($this->settings['outputFileName']) {
                $fileName = $this->utilityFuncs->getSingle($this->settings, 'outputFileName');
            }
            $csv->output($fileName, $data, $fields);
            die();
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Generator/Tx_Formhandler_AbstractGenerator#getComponentLinkParams($linkGP)
    */
    protected function getComponentLinkParams($linkGP)
    {
        $prefix = $this->globals->getFormValuesPrefix();
        $tempParams = [
            'action' => 'csv'
        ];
        $params = [];
        if ($prefix) {
            $params[$prefix] = $tempParams;
        } else {
            $params = $tempParams;
        }
        return $params;
    }
}
