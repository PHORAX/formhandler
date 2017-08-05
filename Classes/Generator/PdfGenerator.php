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
 * PDF generator class for Formhandler using the extension "pdf_generator2"
 */
class PdfGenerator extends AbstractGenerator
{

    /**
     * Renders the PDF.
     *
     * @return void
     */
    public function process()
    {
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
        $type = 123;
        if ($this->settings['type']) {
            $type = $this->settings['type'];
        }
        $params['type'] = $type;
        return $params;
    }
}
