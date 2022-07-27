<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Generator;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\View\File as ViewFile;

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
 * Generator class for Formhandler for creating any file type.
 */
class File extends AbstractGenerator {
  /**
   * Renders the XML file.
   */
  public function process(mixed &$error = null): array|string {
    /** @var ViewFile $view */
    $view = GeneralUtility::makeInstance(ViewFile::class);
    $this->filename = '';
    if (1 == intval($this->settings['storeInTempFile'])) {
      $this->outputPath = $this->utilityFuncs->getDocumentRoot();
      if ($this->settings['customTempOutputPath']) {
        $this->outputPath .= $this->utilityFuncs->sanitizePath(strval($this->settings['customTempOutputPath'] ?? ''));
      } else {
        $this->outputPath .= '/typo3temp/';
      }
      $this->filename = $this->outputPath.$this->settings['filePrefix'].$this->utilityFuncs->generateHash().'.xml';
    }

    $this->filenameOnly = $this->utilityFuncs->getSingle($this->settings, 'staticFileName');
    if (0 === strlen($this->filenameOnly)) {
      $this->filenameOnly = basename($this->filename);
    }

    $this->formhandlerSettings = $this->globals->getSettings();
    $suffix = $this->formhandlerSettings['templateSuffix'];
    $this->template = $this->utilityFuncs->readTemplateFile('', $this->formhandlerSettings);
    if ($this->settings['templateFile']) {
      $this->template = $this->utilityFuncs->readTemplateFile('', $this->settings);
    }
    if ($suffix) {
      $view->setTemplate($this->template, 'FILE'.$suffix);
    }
    if (!$view->hasTemplate()) {
      $view->setTemplate($this->template, 'FILE');
    }
    if (!$view->hasTemplate()) {
      $this->utilityFuncs->throwException('No FILE template found');
    }

    $view->setComponentSettings($this->settings);
    $content = $view->render($this->gp, []);

    $returns = $this->settings['returnFileName'];
    $contentType = $this->utilityFuncs->getSingle($this->settings, 'contentType');
    if (!$contentType) {
      $contentType = 'text/plain';
    }
    if (!empty($this->filename)) {
      $fp = fopen($this->filename, 'w');
      if (!is_bool($fp)) {
        fwrite($fp, $content);
        fclose($fp);
        $downloadpath = $this->filename;
        if ($returns) {
          return $downloadpath;
        }
        $downloadpath = str_replace($this->utilityFuncs->getDocumentRoot(), '', $downloadpath);
        header('Content-type: '.$contentType);
        header('Location: '.$downloadpath);

        exit;
      }
    }
    header('Content-type: '.$contentType);
    header('Content-Disposition: attachment; filename="'.$this->filenameOnly.'"');
    echo $content;

    exit;
  }

  /**
   * @param array<string, mixed> $linkGP
   *
   * @return array<string, mixed>
   */
  protected function getComponentLinkParams(array $linkGP): array {
    $prefix = $this->globals->getFormValuesPrefix();
    $tempParams = ['action' => 'file'];
    $params = [];

    if ($prefix) {
      $params[$prefix] = $tempParams;
    } else {
      $params = $tempParams;
    }

    return $params;
  }
}
