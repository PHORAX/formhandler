<?php
namespace Typoheads\Formhandler\Controller;

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
use Typoheads\Formhandler\Component\AbstractClass;

/**
 * Abstract class for Controller Classes used by Formhandler.
 * @abstract
 */
abstract class AbstractController extends AbstractClass
{

    /**
     * The content returned by the controller
     *
     * @access protected
     * @var Content
     */
    protected $content;

    /**
     * The key of a possibly selected predefined form
     *
     * @access protected
     * @var string
     */
    protected $predefined;

    /**
     * The template file to be used. Only if template file was defined via plugin record
     *
     * @access protected
     * @var string
     */
    protected $templateFile;

    /**
     * Array of configured translation files
     *
     * @access protected
     * @var array
     */
    protected $langFiles;

    /**
     * Sets the content attribute of the controller
     *
     * @param Content $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Returns the content attribute of the controller
     *
     * @return Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the internal attribute "predefined"
     *
     * @param string $key
     * @return void
     */
    public function setPredefined($key)
    {
        $this->predefined = $key;
    }

    /**
     * Sets the internal attribute "langFile"
     *
     * @param array $langFiles
     * @return void
     */
    public function setLangFiles($langFiles)
    {
        $this->langFiles = $langFiles;
    }

    /**
     * Sets the template file attribute to $template
     * @param string $template
     * @return void
     */
    public function setTemplateFile($template)
    {
        $this->templateFile = $template;
    }

    /**
     * Returns the right settings for the formhandler (Checks if predefined form was selected)
     *
     * @return array The settings
     */
    public function getSettings()
    {
        $settings = $this->configuration->getSettings();
        if ($this->predefined && is_array($settings['predef.'][$this->predefined])) {
            $predefSettings = $settings['predef.'][$this->predefined];
            unset($settings['predef.']);
            $settings = $this->utilityFuncs->mergeConfiguration($settings, $predefSettings);
        }
        return $settings;
    }
}
