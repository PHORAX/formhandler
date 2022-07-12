<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Utility;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
 * A PDF Template class for Formhandler generated PDF files for usage with Generator_TCPDF.
 */
class TemplateTCPDF extends \TCPDF {
  /**
   * Text for the footer.
   */
  protected string $footerText = '';

  /**
   * Text for the header.
   */
  protected string $headerText = '';

  /**
   * Path to language file.
   */
  protected string $sysLangFile = '';

  public function __construct() {
    parent::__construct();
    $this->sysLangFile = 'EXT:formhandler/Resources/Private/Language/locallang.xlf';
  }

  /**
   * Generates the footer.
   */
  public function Footer(): void {
    // Position at 1.5 cm from bottom
    $this->SetY(-15);

    $footerText = $this->getFooterText();

    if (strlen($footerText) > 0) {
      $footerText = str_ireplace(
        [
          '###PDF_PAGE_NUMBER###',
          '###PDF_TOTAL_PAGES###',
        ],
        [
          $this->getAliasNumPage(),
          $this->getAliasNbPages(),
        ],
        $footerText
      );
      $this->Cell(0, 10, $footerText, 'T', 0, 'C');
    } else {
      $text = $this->getLL('footer_text');
      $text = sprintf($text, date('d.m.Y H:i:s', time()));
      $this->Cell(0, 10, $text, 'T', 0, 'C');
      $pageNumbers = $this->getLL('page').' '.$this->getAliasNumPage().'/'.$this->getAliasNbPages();
      $this->Cell(0, 10, $pageNumbers, 'T', 0, 'R');
    }
  }

  /**
   * Returns the string used as PDF Footer text.
   */
  public function getFooterText(): string {
    return $this->footerText;
  }

  /**
   * Returns the string used as PDF Footer text.
   */
  public function getHeaderText(): string {
    return $this->headerText;
  }

  /**
   * Generates the header of the page.
   */
  public function Header(): void {
    $headerText = $this->getHeaderText();
    if (strlen($headerText) > 0) {
      $this->SetY(5);

      $text = str_ireplace(
        [
          '###PDF_PAGE_NUMBER###',
          '###PDF_TOTAL_PAGES###',
        ],
        [
          $this->PageNo(),
          $this->numpages,
        ],
        $headerText
      );
      $this->Cell(0, 10, $text, 'B', 0, 'C');
    }
  }

  /**
   * Set the text for the PDF Footer.
   *
   * @param string $s The string to set as PDF Header Text
   */
  public function setFooterText(string $s): void {
    $this->footerText = $s;
  }

  /**
   * Set the text for the PDF Header.
   *
   * @param string $s The string to set as PDF Header Text
   */
  public function setHeaderText(string $s): void {
    $this->headerText = $s;
  }

  /**
   * Get a translation for given key from "EXT:formhandler/Resources/Private/Language/locallang.xlf".
   *
   * @param string $key The key
   *
   * @return string The translation
   */
  private function getLL(string $key): string {
    global $LANG;
    if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
      $LANG->includeLLFile($this->sysLangFile);
      $text = trim($LANG->getLL($key));
    } else {
      $text = trim(LocalizationUtility::translate('LLL:'.$this->sysLangFile.':'.$key));
    }

    return $text;
  }
}
