<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Generator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Component\AbstractComponent;
use Typoheads\Formhandler\Utility\TemplateTCPDF;

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
 * Class to generate PDF files in Backend.
 *
 * @uses Tx_Formhandler_Template_TCPDF
 */
class BackendTcPdf extends AbstractComponent {
  public function init(array $gp, array $settings): void {
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

  public function process(mixed &$error = null): array|string {
    $records = (array) ($this->settings['records'] ?? []);
    $exportFields = (array) ($this->settings['exportFields'] ?? []);

    // init pdf object

    /** @var TemplateTCPDF $pdf */
    $pdf = GeneralUtility::makeInstance(TemplateTCPDF::class);

    $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

    $font = strval($this->settings['font'] ?? '');
    $pdf->SetFont($font, '', floatval($this->settings['fontSize']));
    $pdf->SetHeaderFont([$font, '', floatval($this->settings['fontSizeHeader'])]);
    $pdf->SetFooterFont([$font, '', floatval($this->settings['fontSizeFooter'])]);

    $addedOneRecord = false;

    // for all records,
    // check if the record is valid.
    // a valid record has at least one param to export
    // if no valid record is found render an error message in pdf file
    foreach ($records as $data) {
      $valid = false;
      if (is_array($data)) {
        if (isset($data['params']) && is_array($data['params'])) {
          foreach ($data['params'] as $key => $value) {
            if (0 == count($exportFields) || in_array($key, $exportFields)) {
              $valid = true;
            }
          }
        }
        if ($valid) {
          $addedOneRecord = true;
          $pdf->AddPage();
          $standardWidth = 100;
          $nameWidth = 70;
          $valueWidth = 70;
          $feedWidth = 30;

          if (0 == count($exportFields) || in_array('pid', $exportFields)) {
            $pdf->Cell($standardWidth, 15.0, 'Page-ID:', 0, 0);
            $pdf->Cell($standardWidth, 15.0, $data['pid'], 0, 1);
          }
          if (0 == count($exportFields) || in_array('submission_date', $exportFields)) {
            $pdf->Cell($standardWidth, 15.0, 'Submission date:', 0, 0);
            $pdf->Cell($standardWidth, 15.0, date('d.m.Y H:i:s', $data['crdate']), 0, 1);
          }
          if (0 == count($exportFields) || in_array('ip', $exportFields)) {
            $pdf->Cell($standardWidth, 15.0, 'IP address:', 0, 0);
            $pdf->Cell($standardWidth, 15.0, $data['ip'], 0, 1);
          }

          $pdf->Cell($standardWidth, 15.0, 'Submitted values:', 0, 1);
          $pdf->SetLineWidth(.3);
          $pdf->Cell($feedWidth);
          $pdf->SetFillColor(255, 255, 255);
          $pdf->Cell($nameWidth, 6.0, 'Name', 'B', 0, 'C', true);
          $pdf->Cell($valueWidth, 6.0, 'Value', 'B', 0, 'C', true);
          $pdf->Ln();
          $pdf->SetFillColor(200, 200, 200);
          $fill = false;

          if (0 == count($exportFields)) {
            $exportFields = array_keys($data['params']);
          }
          foreach ($exportFields as $idx => $key) {
            $key = strval($key);
            if (isset($data['params'][$key])) {
              $value = $data['params'][$key];
              if (is_array($value)) {
                $pdf->Cell($feedWidth);
                $pdf->Cell($nameWidth, 6.0, $key, 0, 0, 'L', $fill);
                $arrayValue = array_shift($value);
                if (false === strpos($arrayValue, "\n") && false === strpos($arrayValue, "\r") && strlen($arrayValue) < $valueWidth - 40) {
                  $pdf->Cell($valueWidth, 6.0, $arrayValue, 0, 0, 'L', $fill);
                } else {
                  $pdf->MultiCell($valueWidth, 6.0, $arrayValue, 0, 'L', $fill, 0);
                }
                $pdf->Ln();
                foreach ($value as $v) {
                  $pdf->Cell($feedWidth);
                  $pdf->Cell($nameWidth, 6.0, '', 0, 0, 'L', $fill);
                  if (false === strpos($v, "\n") && false === strpos($v, "\r") && strlen($v) < $valueWidth - 40) {
                    $pdf->Cell($valueWidth, 6.0, $v, 0, 0, 'L', $fill);
                  } else {
                    $pdf->MultiCell($valueWidth, 6.0, $v, 0, 'L', $fill, 0);
                  }
                  $pdf->Ln();
                }
                $fill = !$fill;
              } else {
                $pdf->Cell($feedWidth);
                $pdf->Cell($nameWidth, 6.0, $key, 0, 0, 'L', $fill);
                if (false === strpos($value, "\n") && false === strpos($value, "\r") && strlen($value) < $valueWidth - 40) {
                  $pdf->Cell($valueWidth, 6.0, $value, 0, 0, 'L', $fill);
                } else {
                  $pdf->MultiCell($valueWidth, 6.0, $value, 0, 'L', $fill, 0);
                }
                $pdf->Ln();
                $fill = !$fill;
              }
            }
          }
        }
      }
    }

    // if no valid record was found, render an error message
    if (!$addedOneRecord) {
      $pdf->AddPage();
      $pdf->Cell(300, 100, 'No valid records found! Try to select more fields to export!', 0, 0, 'L');
    }

    $fileName = strval($this->settings['fileName'] ?? '');
    if (is_writable(dirname($fileName))) {
      $pdf->Output($fileName, 'D');
    }

    exit;
  }

  /**
   * Sets the template code for the PDF.
   *
   * @param string $template The template code
   */
  public function setTemplateCode(string $template): void {
    $this->template = $template;
  }
}
