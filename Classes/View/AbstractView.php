<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\View;

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Controller\Configuration;
use Typoheads\Formhandler\Utility\GeneralUtility;
use Typoheads\Formhandler\Utility\Globals;

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
abstract class AbstractView extends AbstractPlugin
{

    /**
     * The prefix id
     *
     * @var string
     */
    public string $prefixId = 'Tx_Formhandler';

    /**
     * The extension key
     *
     * @var string
     */
    public string $extKey = 'formhandler';

    /**
     * The cObj for link generation in FE
     *
     * @var tslib_cObj
     */
    public ContentObjectRenderer $cObj;

    /**
     * @var \TYPO3\CMS\Core\Service\MarkerBasedTemplateService
     */
    protected MarkerBasedTemplateService $markerBasedTemplateService;

    /**
     * The piVars
     *
     * @var array
     */
    public array $piVars = [];

    /**
     * The Formhandler component manager
     *
     * @var \Typoheads\Formhandler\Component\Manager
     */
    protected Manager $componentManager;

    /**
     * The global Formhandler configuration
     *
     * @var \Typoheads\Formhandler\Controller\Configuration
     */
    protected Configuration $configuration;

    /**
     * The global Formhandler values
     *
     * @var \Typoheads\Formhandler\Utility\Globals
     */
    protected Globals $globals;

    /**
     * The Formhandler utility methods
     *
     * @var \Typoheads\Formhandler\Utility\GeneralUtility
     */
    protected GeneralUtility $utilityFuncs;

    /**
     * The model of the view
     *
     * @var misc
     */
    protected mixed $model;

    /**
     * The subparts array
     *
     * @var array
     */
    protected array $subparts = [];

    /**
     * The template code
     *
     * @var string
     */
    protected string $template = '';

    /**
     * An array of translation file names
     *
     * @var array
     */
    protected array $langFiles = [];

    /**
     * The get/post parameters
     *
     * @var array
     */
    protected array $gp = [];

    protected array $settings = [];

    protected array $componentSettings = [];

    /**
     * The constructor for a view setting the component manager and the configuration.
     *
     * @param \Typoheads\Formhandler\Component\Manager $componentManager
     * @param \Typoheads\Formhandler\Controller\Configuration $configuration
     */
    public function __construct(
        Manager $componentManager,
        Configuration $configuration,
        Globals $globals,
        GeneralUtility $utilityFuncs
    ) {
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
     */
    public function setLangFiles(array $langFiles): void
    {
        $this->langFiles = $langFiles;
    }

    /**
     * Sets the settings
     *
     * @param array $settings The settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function setComponentSettings(array $settings): void
    {
        $this->componentSettings = $settings;
    }

    public function getComponentSettings(): array
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
     */
    public function setPredefined(string $key): void
    {
        $this->predefined = $key;
    }

    /**
     * Sets the model of the view
     *
     * @param misc $model
     */
    public function setModel(mixed $model): void
    {
        $this->model = $model;
    }

    /**
     * Returns the model of the view
     *
     * @return misc $model
     */
    public function getModel(): mixed
    {
        return $this->model;
    }

    /**
     * Sets the template of the view
     *
     * @param string $templateCode The whole template code of a template file
     * @param string $templateName Name of a subpart containing the template code to work with
     * @param bool $forceTemplate Not needed
     */
    public function setTemplate(string $templateCode, string $templateName, bool $forceTemplate = false): void
    {
        $this->subparts['template'] = $this->markerBasedTemplateService->getSubpart($templateCode, '###TEMPLATE_' . $templateName . '###');
        $this->subparts['item'] = $this->markerBasedTemplateService->getSubpart($this->subparts['template'], '###ITEM###');
    }

    /**
     * Returns false if the view doesn't have template code.
     *
     * @return bool
     */
    public function hasTemplate(): bool
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
    abstract public function render(array $gp, array $errors): string;

    /**
     * Overwrite this method to extend the initialization of the View
     *
     * @author Jochen Rau
     */
    protected function initializeView(): void
    {
    }

    /**
     * Returns given string in uppercase
     *
     * @param string $camelCase The string to transform
     * @return string Parsed string
     * @author Jochen Rau
     */
    protected function getUpperCase(string $camelCase): string
    {
        return strtoupper(preg_replace('/\p{Lu}+(?!\p{Ll})|\p{Lu}/u', '_$0', $camelCase));
    }
}
