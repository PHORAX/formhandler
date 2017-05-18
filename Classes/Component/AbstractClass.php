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
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility as CoreGeneralUtility;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Controller\Configuration;
use Typoheads\Formhandler\Utility\GeneralUtility;
use Typoheads\Formhandler\Utility\Globals;

/**
 * Abstract class for any usable Formhandler component.
 * This class defines some useful variables and a default constructor for all Formhandler components.
 *
 * @author    Reinhard Führicht <rf@typoheads.at>
 * @abstract
 */
abstract class AbstractClass
{

    /**
     * The Formhandler component manager
     *
     * @access protected
     * @var Manager
     */
    protected $componentManager;

    /**
     * The global Formhandler configuration
     *
     * @access protected
     * @var Configuration
     */
    protected $configuration;

    /**
     * The global Formhandler values
     *
     * @access protected
     * @var Globals
     */
    protected $globals;

    /**
     * The Formhandler utility methods
     *
     * @access protected
     * @var GeneralUtility
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
     * @param Manager $componentManager
     * @param Configuration $configuration
     * @param Globals $globals
     * @param GeneralUtility $utilityFuncs
     */
    public function __construct(Manager $componentManager,
                                Configuration $configuration,
                                Globals $globals,
                                GeneralUtility $utilityFuncs)
    {

        $this->componentManager = $componentManager;
        $this->configuration = $configuration;
        $this->globals = $globals;
        $this->utilityFuncs = $utilityFuncs;
        $this->templateService = CoreGeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $this->cObj = $this->globals->getCObj();
    }
}