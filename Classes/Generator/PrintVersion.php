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
 * Generator class for Formhandler showing a print version of the SUBMITTED_OK template
 */
class PrintVersion extends AbstractGenerator
{

    /**
     * Unused
     */
    public function process()
    {
    }

    /* (non-PHPdoc)
     * @see Classes/Generator/AbstractGenerator#getLinkText()
    */
    protected function getLinkText()
    {
        $text = $this->utilityFuncs->getSingle($this->settings, 'linkText');
        if (strlen($text) == 0) {
            $text = $this->utilityFuncs->getTranslatedMessage($this->globals->getLangFiles(), 'print');
        }
        if (strlen($text) === 0) {
            $text = 'Print';
        }
        return $text;
    }

    /* (non-PHPdoc)
     * @see Classes/Generator/AbstractGenerator#getComponentLinkParams($linkGP)
    */
    protected function getComponentLinkParams($linkGP)
    {
        $prefix = $this->globals->getFormValuesPrefix();
        $tempParams = [
            'action' => 'show'
        ];
        $params = [];
        if ($prefix) {
            $params[$prefix] = $tempParams;
        } else {
            $params = $tempParams;
        }
        $params['type'] = 98;
        return $params;
    }
}
