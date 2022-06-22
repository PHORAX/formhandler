<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\AjaxHandler;

use Typoheads\Formhandler\Component\AbstractClass;

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
 * Abstract class for an AjaxHandler.
 * The AjaxHandler takes care of adding AJAX related markers and JS used for validation and file removal.
 */
abstract class AbstractAjaxHandler extends AbstractClass {
  /**
   * Method called by the view to let the AjaxHandler add its markers.
   *
   * The view passes the marker array by reference.
   *
   * @param array<string, mixed> &$markers Reference to the marker array
   */
  abstract public function fillAjaxMarkers(array &$markers): void;

  /**
   * Method called by the view to get an AJAX based file removal link.
   *
   * @param string $text             The link text to be used
   * @param string $field            The field name of the form field
   * @param string $uploadedFileName The name of the file to be deleted
   */
  abstract public function getFileRemovalLink(string $text, string $field, string $uploadedFileName): string;

  /**
   * Initialize the AjaxHandler.
   *
   * @param array<string, mixed> $settings The settings of the AjaxHandler
   */
  public function init(array $settings): void {
    $this->settings = $settings;
  }

  /**
   * Initialize AJAX stuff.
   */
  abstract public function initAjax(): void;
}
