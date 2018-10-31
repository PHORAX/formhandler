<?php
namespace Typoheads\Formhandler\Utility;

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
 * A PDF Template class for Formhandler generated PDF files for usage with Generator_TCPDF.
 */
class TemplateTCPDF extends \TCPDF
{

    /**
     * Path to language file
     *
     * @access protected
     * @var string
     */
    protected $sysLangFile;

    /**
     * Text for the header
     *
     * @access protected
     * @var string
     */
    protected $headerText;

    /**
     * Text for the footer
     *
     * @access protected
     * @var string
     */
    protected $footerText;

    public function __construct()
    {
        parent::__construct();
        $this->sysLangFile = 'EXT:formhandler/Resources/Private/Language/locallang.xml';
    }

    /**
     * Generates the header of the page
     *
     * @return void
     */
    public function Header()
    {
        $headerText = $this->getHeaderText();
        if (strlen($headerText) > 0) {
            $this->SetY(5);

            $text = str_ireplace(
                [
                    '###PDF_PAGE_NUMBER###',
                    '###PDF_TOTAL_PAGES###'
                ],
                [
                    $this->PageNo(),
                    $this->numpages
                ],
                $headerText
            );
            $this->Cell(0, 10, $text, 'B', 0, 'C');
        }
    }

    /**
     * Generates the footer
     *
     * @return void
     */
    public function Footer()
    {

        //Position at 1.5 cm from bottom
        $this->SetY(-15);

        $footerText = $this->getFooterText();

        if (strlen($footerText) > 0) {
            $footerText = str_ireplace(
                [
                    '###PDF_PAGE_NUMBER###',
                    '###PDF_TOTAL_PAGES###'
                ],
                [
                    $this->getAliasNumPage(),
                    $this->getAliasNbPages()
                ],
                $footerText
            );
            $this->Cell(0, 10, $footerText, 'T', 0, 'C');
        } else {
            $text = $this->getLL('footer_text');
            $text = sprintf($text, date('d.m.Y H:i:s', time()));
            $this->Cell(0, 10, $text, 'T', 0, 'C');
            $pageNumbers = $this->getLL('page') . ' ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
            $this->Cell(0, 10, $pageNumbers, 'T', 0, 'R');
        }
    }

    /**
     * Get a translation for given key from "EXT:formhandler/Resources/Private/Language/locallang.xml"
     *
     * @param string $key The key
     * @return string The translation
     */
    private function getLL($key)
    {
        global $LANG;
        if (TYPO3_MODE == 'BE') {
            $LANG->includeLLFile($this->sysLangFile);
            $text = trim($LANG->getLL($key));
        } else {
            $text = trim($GLOBALS['TSFE']->sL('LLL:' . $this->sysLangFile . ':' . $key));
        }
        return $text;
    }

    /**
     * Set the text for the PDF Header
     *
     * @param string $s The string to set as PDF Header Text
     */
    public function setHeaderText($s)
    {
        $this->headerText = $s;
    }

    /**
     * Set the text for the PDF Footer
     *
     * @param string $s The string to set as PDF Header Text
     */
    public function setFooterText($s)
    {
        $this->footerText = $s;
    }

    /**
     * Returns the string used as PDF Footer text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return $this->headerText;
    }

    /**
     * Returns the string used as PDF Footer text
     *
     * @return string
     */
    public function getFooterText()
    {
        return $this->footerText;
    }
}
