<?php
namespace Typoheads\Formhandler\Generator;

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
 * Abstract generator class for Formhandler
 */
abstract class AbstractGenerator extends \Typoheads\Formhandler\Component\AbstractComponent
{

    /**
     * Returns the link with the right action set to be used in Finisher_SubmittedOK
     *
     * @param array $linkGP The GET parameters to set
     * @return string The link
     */
    public function getLink($linkGP = [])
    {
        $text = $this->getLinkText();

        $params = $this->getDefaultLinkParams();
        $componentParams = $this->getComponentLinkParams($linkGP);
        if (is_array($componentParams)) {
            $params = $this->utilityFuncs->mergeConfiguration($params, $componentParams);
        }
        return $this->cObj->getTypolink($text, $GLOBALS['TSFE']->id, $params, $this->getLinkTarget());
    }

    /**
     * Returns the default link parameters of a generator containing the timestamp and hash of the log record.
     *
     * @return array The default parameters
     */
    protected function getDefaultLinkParams()
    {
        $prefix = $this->globals->getFormValuesPrefix();
        $tempParams = [
            'tstamp' => $this->globals->getSession()->get('inserted_tstamp'),
            'hash' => $this->globals->getSession()->get('unique_hash')
        ];
        $params = [];
        if ($prefix) {
            $params[$prefix] = $tempParams;
        } else {
            $params = $tempParams;
        }

        if (is_array($this->settings['additionalParams.'])) {
            foreach ($this->settings['additionalParams.'] as $param => $value) {
                if (false === strpos($param, '.')) {
                    if (is_array($this->settings['additionalParams.'][$param . '.'])) {
                        $value = $this->utilityFuncs->getSingle($this->settings['additionalParams.'], $param);
                    }
                    $params[$param] = $value;
                }
            }
        }
        return $params;
    }

    /**
     * Returns specific link parameters for a generator.
     *
     * @param array $linkGP The link parameters set before.
     * @return array The parameters
     */
    abstract protected function getComponentLinkParams($linkGP);

    /**
     * Returns the link text.
     *
     * @return string The link text
     */
    protected function getLinkText()
    {
        $text = $this->utilityFuncs->getSingle($this->settings, 'linkText');
        if (strlen($text) === 0) {
            $text = 'Save';
        }
        return $text;
    }

    /**
     * Returns the link target.
     *
     * @return string The link target
     */
    protected function getLinkTarget()
    {
        $target = $this->utilityFuncs->getSingle($this->settings, 'linkTarget');
        if (strlen($target) === 0) {
            $target = '_self';
        }
        return $target;
    }
}
