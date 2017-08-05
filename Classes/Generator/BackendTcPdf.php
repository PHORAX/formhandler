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
 * Class to generate PDF files in Backend
 * @uses Tx_Formhandler_Template_TCPDF
 */
class BackendTcPdf extends \Typoheads\Formhandler\Component\AbstractComponent
{

    /**
     * The internal PDF object
     *
     * @access protected
     * @var Tx_Formhandler_Template_TCPDF
     */
    protected $pdf;

    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $fileName = $this->utilityFuncs->getSingle($this->settings, 'fileName');
        if (!$fileName) {
            $fileName = 'formhandler.pdf';
        }
        $this->settings['fileName'] = $fileName;

        $fontSize = $this->utilityFuncs->getSingle($this->settings, 'fontSize');
        if (!$fontSize) {
            $fontSize = 12;
        }
        $this->settings['fontSize'] = $fontSize;

        $fontSizeHeader = $this->utilityFuncs->getSingle($this->settings, 'fontSizeHeader');
        if (!$fontSizeHeader) {
            $fontSizeHeader = 8;
        }
        $this->settings['fontSizeHeader'] = $fontSizeHeader;

        $fontSizeFooter = $this->utilityFuncs->getSingle($this->settings, 'fontSizeFooter');
        if (!$fontSizeFooter) {
            $fontSizeFooter = 8;
        }
        $this->settings['fontSizeFooter'] = $fontSizeFooter;

        $font = $this->utilityFuncs->getSingle($this->settings, 'font');
        if (!$font) {
            $font = 'FreeSans';
        }
        $this->settings['font'] = $font;
    }

    /**
     * @return void
     */
    public function process()
    {
        $records = $this->settings['records'];
        $exportFields = $this->settings['exportFields'];

        //init pdf object
        $this->pdf = $this->componentManager->getComponent('Typoheads\Formhandler\Utility\TemplateTCPDF');
        $this->pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        $this->pdf->SetFont($this->settings['font'], '', $this->settings['fontSize']);
        $this->pdf->SetHeaderFont([$this->settings['font'], '', $this->settings['fontSizeHeader']]);
        $this->pdf->SetFooterFont([$this->settings['font'], '', $this->settings['fontSizeFooter']]);

        $addedOneRecord = false;

        //for all records,
        //check if the record is valid.
        //a valid record has at least one param to export
        //if no valid record is found render an error message in pdf file
        foreach ($records as $data) {
            $valid = false;
            if (isset($data['params']) && is_array($data['params'])) {
                foreach ($data['params'] as $key => $value) {
                    if (count($exportFields) == 0 || in_array($key, $exportFields)) {
                        $valid = true;
                    }
                }
            }
            if ($valid) {
                $addedOneRecord = true;
                $this->pdf->AddPage();
                $standardWidth = 100;
                $nameWidth = 70;
                $valueWidth = 70;
                $feedWidth = 30;

                if (count($exportFields) == 0 || in_array('pid', $exportFields)) {
                    $this->pdf->Cell($standardWidth, '15', 'Page-ID:', 0, 0);
                    $this->pdf->Cell($standardWidth, '15', $data['pid'], 0, 1);
                }
                if (count($exportFields) == 0 || in_array('submission_date', $exportFields)) {
                    $this->pdf->Cell($standardWidth, '15', 'Submission date:', 0, 0);
                    $this->pdf->Cell($standardWidth, '15', date('d.m.Y H:i:s', $data['crdate']), 0, 1);
                }
                if (count($exportFields) == 0 || in_array('ip', $exportFields)) {
                    $this->pdf->Cell($standardWidth, '15', 'IP address:', 0, 0);
                    $this->pdf->Cell($standardWidth, '15', $data['ip'], 0, 1);
                }

                $this->pdf->Cell($standardWidth, '15', 'Submitted values:', 0, 1);
                $this->pdf->SetLineWidth(.3);
                $this->pdf->Cell($feedWidth);
                $this->pdf->SetFillColor(255, 255, 255);
                $this->pdf->Cell($nameWidth, '6', 'Name', 'B', 0, 'C', true);
                $this->pdf->Cell($valueWidth, '6', 'Value', 'B', 0, 'C', true);
                $this->pdf->Ln();
                $this->pdf->SetFillColor(200, 200, 200);
                $fill = false;

                if (count($exportFields) == 0) {
                    $exportFields = array_keys($data['params']);
                }
                foreach ($exportFields as $idx => $key) {
                    if (isset($data['params'][$key])) {
                        $value = $data['params'][$key];
                        if (is_array($value)) {
                            $this->pdf->Cell($feedWidth);
                            $this->pdf->Cell($nameWidth, '6', $key, 0, 0, 'L', $fill);
                            $arrayValue = array_shift($value);
                            if (strpos($arrayValue, "\n") === false && strpos($arrayValue, "\r") === false && strlen($arrayValue) < $valueWidth - 40) {
                                $this->pdf->Cell($valueWidth, '6', $arrayValue, 0, 0, 'L', $fill);
                            } else {
                                $this->pdf->MultiCell($valueWidth, '6', $arrayValue, 0, 0, 'L', $fill);
                            }
                            $this->pdf->Ln();
                            foreach ($value as $v) {
                                $this->pdf->Cell($feedWidth);
                                $this->pdf->Cell($nameWidth, '6', '', 0, 0, 'L', $fill);
                                if (strpos($v, "\n") === false && strpos($v, "\r") === false && strlen($v) < $valueWidth - 40) {
                                    $this->pdf->Cell($valueWidth, '6', $v, 0, 0, 'L', $fill);
                                } else {
                                    $this->pdf->MultiCell($valueWidth, '6', $v, 0, 0, 'L', $fill);
                                }
                                $this->pdf->Ln();
                            }
                            $fill = !$fill;
                        } else {
                            $this->pdf->Cell($feedWidth);
                            $this->pdf->Cell($nameWidth, '6', $key, 0, 0, 'L', $fill);
                            if (strpos($value, "\n") === false && strpos($value, "\r") === false && strlen($value) < $valueWidth - 40) {
                                $this->pdf->Cell($valueWidth, '6', $value, 0, 0, 'L', $fill);
                            } else {
                                $this->pdf->MultiCell($valueWidth, '6', $value, 0, 0, 'L', $fill);
                            }
                            $this->pdf->Ln();
                            $fill = !$fill;
                        }
                    }
                }
            }
        }

        //if no valid record was found, render an error message
        if (!$addedOneRecord) {
            $this->pdf->AddPage();
            $this->pdf->Cell(300, 100, 'No valid records found! Try to select more fields to export!', 0, 0, 'L');
        }

        $this->pdf->Output($this->settings['fileName'], 'D');
        exit;
    }

    /**
     * Sets the template code for the PDF
     *
     * @param string $templateCode The template code
     * @return void
     */
    public function setTemplateCode($templateCode)
    {
        $this->templateCode = $templateCode;
    }
}
