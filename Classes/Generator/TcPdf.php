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
 * PDF generator class for Formhandler using TCPDF
 */
class TcPdf extends AbstractGenerator
{

    /**
     * @return mixed
     */
    public function process()
    {
        $this->pdf = $this->componentManager->getComponent('\Typoheads\Formhandler\Utility\TemplateTCPDF');

        $this->pdf->setHeaderText($this->utilityFuncs->getSingle($this->settings, 'headerText'));
        $this->pdf->setFooterText($this->utilityFuncs->getSingle($this->settings, 'footerText'));

        $this->pdf->AddPage();
        $this->pdf->SetFont('Helvetica', '', 12);
        $view = $this->componentManager->getComponent('\Typoheads\Formhandler\View\PDF');
        $this->filename = false;
        if (intval($this->settings['storeInTempFile']) === 1) {
            $this->outputPath = $this->utilityFuncs->getDocumentRoot();
            if ($this->settings['customTempOutputPath']) {
                $this->outputPath .= $this->settings['customTempOutputPath'];
            } else {
                $this->outputPath .= '/typo3temp/';
            }
            $this->outputPath = $this->utilityFuncs->sanitizePath($this->outputPath);
            $this->filename = $this->outputPath . $this->settings['filePrefix'] . $this->utilityFuncs->generateHash() . '.pdf';

            $this->filenameOnly = $this->utilityFuncs->getSingle($this->settings, 'staticFileName');
            if (strlen($this->filenameOnly) === 0) {
                $this->filenameOnly = basename($this->filename);
            }
        }

        $this->formhandlerSettings = $this->globals->getSettings();
        $suffix = $this->formhandlerSettings['templateSuffix'];
        $this->templateCode = $this->utilityFuncs->readTemplateFile(false, $this->formhandlerSettings);
        if ($this->settings['templateFile']) {
            $this->templateCode = $this->utilityFuncs->readTemplateFile(false, $this->settings);
        }
        if ($suffix) {
            $view->setTemplate($this->templateCode, 'PDF' . $suffix);
        }
        if (!$view->hasTemplate()) {
            $view->setTemplate($this->templateCode, 'PDF');
        }
        if (!$view->hasTemplate()) {
            $this->utilityFuncs->throwException('no_pdf_template');
        }

        $view->setComponentSettings($this->settings);
        $content = $view->render($this->gp, []);

        $this->pdf->writeHTML($content);
        $returns = $this->settings['returnFileName'];

        if ($this->filename !== false) {
            $this->pdf->Output($this->filename, 'F');

            $downloadpath = $this->filename;
            if ($returns) {
                return $downloadpath;
            }
            $downloadpath = str_replace($this->utilityFuncs->getDocumentRoot(), '', $downloadpath);
            header('Location: ' . $downloadpath);
            exit;
        } else {
            $fileName = 'formhandler.pdf';
            if ($this->settings['outputFileName']) {
                $fileName = $this->utilityFuncs->getSingle($this->settings, 'outputFileName');
            }
            $this->pdf->Output($fileName, 'D');
            exit;
        }
    }

    /* (non-PHPdoc)
     * @see Classes/Generator/Tx_Formhandler_AbstractGenerator#getComponentLinkParams($linkGP)
    */
    protected function getComponentLinkParams($linkGP)
    {
        $prefix = $this->globals->getFormValuesPrefix();
        $tempParams = [
            'action' => 'pdf'
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
