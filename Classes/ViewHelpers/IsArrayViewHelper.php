<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\ViewHelpers\IfViewHelper;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
class IsArrayViewHelper extends IfViewHelper
{
    /**
     * Renders <f:then> child if $condition is true, otherwise renders <f:else> child.
     *
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return bool
     */
    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        return isset($arguments['condition']) && is_array($arguments['condition']);
    }
}
