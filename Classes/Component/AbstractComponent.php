<?php
namespace Typoheads\Formhandler\Component;

/*                                                                       *
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
 * Abstract component class for any usable Formhandler component.
 * This class extends the abstract class and adds some useful variables and methods.
 * @abstract
 */
abstract class AbstractComponent extends AbstractClass
{

    /**
     * The GET/POST parameters
     *
     * @access protected
     * @var array
     */
    protected $gp;

    /**
     * Settings
     *
     * @access protected
     * @var array
     */
    protected $settings;

    /**
     * Initialize the class variables
     *
     * @param array $gp GET and POST variable array
     * @param array $settings Typoscript configuration for the component (component.1.config.*)
     *
     * @return void
     */
    public function init($gp, $settings)
    {
        $this->gp = $gp;
        $this->settings = $settings;
    }

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    abstract public function process();

    public function validateConfig()
    {
        return true;
    }
}
