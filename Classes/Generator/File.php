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
 * Generator class for Formhandler for creating any file type
 */
class File extends AbstractGenerator
{

    /**
     * Renders the XML file.
     *
     * @return void
     */
    public function process()
    {
        $view = $this->componentManager->getComponent('Typoheads\Formhandler\View\File');
        $this->filename = false;
        if (intval($this->settings['storeInTempFile']) === 1) {
            $this->outputPath = $this->utilityFuncs->getDocumentRoot();
            if ($this->settings['customTempOutputPath']) {
                $this->outputPath .= $this->utilityFuncs->sanitizePath($this->settings['customTempOutputPath']);
            } else {
                $this->outputPath .= '/typo3temp/';
            }
            $this->filename = $this->outputPath . $this->settings['filePrefix'] . $this->utilityFuncs->generateHash() . '.xml';
        }

        $this->filenameOnly = $this->utilityFuncs->getSingle($this->settings, 'staticFileName');
        if (strlen($this->filenameOnly) === 0) {
            $this->filenameOnly = basename($this->filename);
        }

        $this->formhandlerSettings = $this->globals->getSettings();
        $suffix = $this->formhandlerSettings['templateSuffix'];
        $this->templateCode = $this->utilityFuncs->readTemplateFile(false, $this->formhandlerSettings);
        if ($this->settings['templateFile']) {
            $this->templateCode = $this->utilityFuncs->readTemplateFile(false, $this->settings);
        }
        if ($suffix) {
            $view->setTemplate($this->templateCode, 'FILE' . $suffix);
        }
        if (!$view->hasTemplate()) {
            $view->setTemplate($this->templateCode, 'FILE');
        }
        if (!$view->hasTemplate()) {
            $this->utilityFuncs->throwException('No FILE template found');
        }

        $view->setComponentSettings($this->settings);
        $content = $view->render($this->gp, array());

        $returns = $this->settings['returnFileName'];
        $contentType = $this->utilityFuncs->getSingle($this->settings, 'contentType');
        if (!$contentType) {
            $contentType = 'text/plain';
        }
        if ($this->filename !== false) {
            $fp = fopen($this->filename, 'w');
            fwrite($fp, $content);
            fclose($fp);
            $downloadpath = $this->filename;
            if ($returns) {
                return $downloadpath;
            }
            $downloadpath = str_replace($this->utilityFuncs->getDocumentRoot(), '', $downloadpath);
            header('Content-type: ' . $contentType);
            header('Location: ' . $downloadpath);
            exit;
        } else {
            header('Content-type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $this->filenameOnly . '"');
            echo $content;
            exit;
        }
    }

    protected function getComponentLinkParams($linkGP)
    {
        $prefix = $this->globals->getFormValuesPrefix();
        $tempParams = array('action' => 'file');
        $params = array();

        if ($prefix) {
            $params[$prefix] = $tempParams;
        } else {
            $params = $tempParams;
        }
        return $params;
    }
}
