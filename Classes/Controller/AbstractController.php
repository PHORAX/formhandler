<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use Typoheads\Formhandler\Component\AbstractClass;

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
 * Abstract class for Controller Classes used by Formhandler.
 * @abstract
 */
abstract class AbstractController extends AbstractClass
{

    /**
     * The content returned by the controller
     *
     * @var Content
     */
    protected Content $content;

    /**
     * The key of a possibly selected predefined form
     *
     * @var string
     */
    protected string $predefined = '';

    /**
     * The template file to be used. Only if template file was defined via plugin record
     *
     * @var string
     */
    protected string $templateFile = '';

    /**
     * Array of configured translation files
     *
     * @var array
     */
    protected array $langFiles = [];

    /**
     * Sets the content attribute of the controller
     *
     * @param Content $content
     */
    public function setContent(Content $content): void
    {
        $this->content = $content;
    }

    /**
     * Returns the content attribute of the controller
     *
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * Sets the internal attribute "predefined"
     *
     * @param string $key
     */
    public function setPredefined(string $key): void
    {
        $this->predefined = $key;
    }

    /**
     * Sets the internal attribute "langFile"
     *
     * @param array $langFiles
     */
    public function setLangFiles(array $langFiles): void
    {
        $this->langFiles = $langFiles;
    }

    /**
     * Sets the template file attribute to $template
     * @param string $template
     */
    public function setTemplateFile(string $template): void
    {
        $this->templateFile = $template;
    }

    /**
     * Returns the right settings for the formhandler (Checks if predefined form was selected)
     *
     * @return array The settings
     */
    public function getSettings(): array
    {
        $settings = $this->configuration->getSettings();
        if ($this->predefined && isset($settings['predef.']) && is_array($settings['predef.']) && isset($settings['predef.'][$this->predefined]) && is_array($settings['predef.'][$this->predefined])) {
            $predefSettings = $settings['predef.'][$this->predefined];
            unset($settings['predef.']);
            $settings = $this->utilityFuncs->mergeConfiguration($settings, $predefSettings);
        }
        return $settings;
    }
}
