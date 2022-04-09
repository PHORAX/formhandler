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
 * A default view for Formhandler AJAX based validation.
 */
class AjaxValidation extends Form {
  /**
   * Wraps the input string in a <div> tag with the class attribute set to the prefixId.
   * All content returned from your plugins should be returned through this function so all content from your plugin is encapsulated in a <div>-tag nicely identifying the content of your plugin.
   *
   * @param string $content HTML content to wrap in the div-tags with the "main class" of the plugin
   *
   * @return string HTML content wrapped, ready to return to the parent object
   */
  public function pi_wrapInBaseClass($content) {
    return $content;
  }
}
