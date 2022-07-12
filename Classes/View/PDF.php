<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\View;

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
 * A default view for Formhandler.
 */
class PDF extends Form {
  /**
   * Main method called by the controller.
   *
   * @param array<string, mixed> $gp     The current GET/POST parameters
   * @param array<string, mixed> $errors The errors occurred in validation
   *
   * @return string content
   */
  public function render(array $gp, array $errors): string {
    $this->gp = $gp;
    $this->settings = $this->parseSettings();

    return parent::render($this->gp, $errors);
  }

  protected function fillValueMarkers(): void {
    $this->disableEncodingFields = [];
    if ($this->settings['disableEncodingFields']) {
      $this->disableEncodingFields = explode(',', $this->utilityFuncs->getSingle($this->settings, 'disableEncodingFields'));
    }
    $markers = $this->getValueMarkers($this->gp);

    $markers = $this->sanitizeMarkers($markers);

    $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);

    // remove remaining VALUE_-markers
    // needed for nested markers like ###LLL:tx_myextension_table.field1.i.###value_field1###### to avoid wrong marker removal if field1 isn't set
    $this->template = preg_replace('/###value_.*?###/i', '', $this->template) ?? '';
  }
}
