<?php
namespace Typoheads\Formhandler\Validator;

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
 * Abstract class for validators for Formhandler
 */
abstract class AbstractValidator extends \Typoheads\Formhandler\Component\AbstractComponent
{

    /**
     * Validates the submitted values using given settings
     *
     * @param array $errors Reference to the errors array to store the errors occurred
     * @return boolean
     */
    abstract public function validate(&$errors);

    public function process()
    {
        return;
    }
}
