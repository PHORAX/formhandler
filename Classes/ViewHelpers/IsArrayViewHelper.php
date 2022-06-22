<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\ViewHelpers\IfViewHelper;

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
class IsArrayViewHelper extends IfViewHelper {
  /**
   * Renders <f:then> child if $condition is true, otherwise renders <f:else> child.
   *
   * @param array<string, mixed> $arguments
   */
  public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool {
    return isset($arguments['condition']) && is_array($arguments['condition']);
  }
}
