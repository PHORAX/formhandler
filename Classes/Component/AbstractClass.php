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
 * Abstract class for any usable Formhandler component.
 * This class defines some useful variables and a default constructor for all Formhandler components.
 * @abstract
 */
abstract class AbstractClass
{

    /**
     * The Formhandler component manager
     *
     * @access protected
     * @var \Typoheads\Formhandler\Component\Manager
     */
    protected $componentManager;

    /**
     * The global Formhandler configuration
     *
     * @access protected
     * @var \Typoheads\Formhandler\Controller\Configuration
     */
    protected $configuration;

    /**
     * The global Formhandler values
     *
     * @access protected
     * @var \Typoheads\Formhandler\Utility\Globals
     */
    protected $globals;

    /**
     * The Formhandler utility methods
     *
     * @access protected
     * @var \Typoheads\Formhandler\Utility\GeneralUtility
     */
    protected $utilityFuncs;

    /**
     * The cObj
     *
     * @access protected
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObj;

    /**
     * The constructor for an interceptor setting the component manager and the configuration.
     *
     * @param \Typoheads\Formhandler\Component\Manager $componentManager
     * @param \Typoheads\Formhandler\Controller\Configuration $configuration
     * @return void
     */
    public function __construct(\Typoheads\Formhandler\Component\Manager $componentManager,
                                \Typoheads\Formhandler\Controller\Configuration $configuration,
                                \Typoheads\Formhandler\Utility\Globals $globals,
                                \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs)
    {
        $this->componentManager = $componentManager;
        $this->configuration = $configuration;
        $this->globals = $globals;
        $this->utilityFuncs = $utilityFuncs;
        $this->cObj = $this->globals->getCObj();
    }
}
