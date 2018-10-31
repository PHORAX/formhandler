<?php
namespace Typoheads\Formhandler\View;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;

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
 * An abstract view for Formhandler
 */
abstract class AbstractView extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

    /**
     * The prefix id
     *
     * @access public
     * @var string
     */
    public $prefixId = 'Tx_Formhandler';

    /**
     * The extension key
     *
     * @access public
     * @var string
     */
    public $extKey = 'formhandler';

    /**
     * The cObj for link generation in FE
     *
     * @access public
     * @var tslib_cObj
     */
    public $cObj;

    /**
     * @var \TYPO3\CMS\Core\Service\MarkerBasedTemplateService
     */
    protected $markerBasedTemplateService;

    /**
     * The piVars
     *
     * @access public
     * @var array
     */
    public $piVars;

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
     * The model of the view
     *
     * @access protected
     * @var misc
     */
    protected $model;

    /**
     * The subparts array
     *
     * @access protected
     * @var array
     */
    protected $subparts;

    /**
     * The template code
     *
     * @access protected
     * @var string
     */
    protected $template;

    /**
     * An array of translation file names
     *
     * @access protected
     * @var array
     */
    protected $langFiles;

    /**
     * The get/post parameters
     *
     * @access protected
     * @var array
     */
    protected $gp;

    protected $componentSettings;

    /**
     * The constructor for a view setting the component manager and the configuration.
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
        parent::__construct();
        $this->componentManager = $componentManager;
        $this->configuration = $configuration;
        $this->globals = $globals;
        $this->utilityFuncs = $utilityFuncs;
        $this->cObj = $this->globals->getCObj();
        $this->markerBasedTemplateService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $this->pi_loadLL();
        $this->initializeView();
    }

    /**
     * Sets the internal attribute "langFiles"
     *
     * @param array $langFiles The files array
     * @return void
     */
    public function setLangFiles($langFiles)
    {
        $this->langFiles = $langFiles;
    }

    /**
     * Sets the settings
     *
     * @param string $settings The settings
     * @return void
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function setComponentSettings($settings)
    {
        $this->componentSettings = $settings;
    }

    public function getComponentSettings()
    {
        if (!is_array($this->componentSettings)) {
            $this->componentSettings = [];
        }
        return $this->componentSettings;
    }

    /**
     * Sets the key of the chosen predefined form
     *
     * @param string $key The key of the predefined form
     * @return void
     */
    public function setPredefined($key)
    {
        $this->predefined = $key;
    }

    /**
     * Sets the model of the view
     *
     * @param misc $model
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Returns the model of the view
     *
     * @return misc $model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the template of the view
     *
     * @param string $templateCode The whole template code of a template file
     * @param string $templateName Name of a subpart containing the template code to work with
     * @param boolean $forceTemplate Not needed
     * @return void
     */
    public function setTemplate($templateCode, $templateName, $forceTemplate = false)
    {
        $this->subparts['template'] = $this->markerBasedTemplateService->getSubpart($templateCode, '###TEMPLATE_' . $templateName . '###');
        $this->subparts['item'] = $this->markerBasedTemplateService->getSubpart($this->subparts['template'], '###ITEM###');
    }

    /**
     * Returns false if the view doesn't have template code.
     *
     * @return boolean
     */
    public function hasTemplate()
    {
        return !empty($this->subparts['template']);
    }

    /**
     * This method performs the rendering of the view
     *
     * @param array $gp The get/post parameters
     * @param array $errors An array with errors occurred whilest validation
     * @return rendered view
     * @abstract
     */
    abstract public function render($gp, $errors);

    /**
     * Overwrite this method to extend the initialization of the View
     *
     * @return void
     * @author Jochen Rau
     */
    protected function initializeView()
    {
    }

    /**
     * Returns given string in uppercase
     *
     * @param string $camelCase The string to transform
     * @return string Parsed string
     * @author Jochen Rau
     */
    protected function getUpperCase($camelCase)
    {
        return strtoupper(preg_replace('/\p{Lu}+(?!\p{Ll})|\p{Lu}/u', '_$0', $camelCase));
    }
}
