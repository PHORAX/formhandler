<?php
namespace Typoheads\Formhandler\PreProcessor;

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
 * A pre processor for Formhandler loading GET/POST parameters passed from another page.
 */
class LoadGetPost extends AbstractPreProcessor
{

    /**
     * Main method called by the controller.
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        $loadedGP = $this->loadGP();
        $this->gp = array_merge($loadedGP, $this->gp);
        return $this->gp;
    }

    /**
     * Loads the GET/POST parameterss into the internal storage $this->gp
     *
     * @return array The loaded parameters
     */
    protected function loadGP()
    {
        $gp = array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET(), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST());
        $formValuesPrefix = $this->globals->getFormValuesPrefix();
        if ($formValuesPrefix) {
            $gp = $gp[$formValuesPrefix];
        }
        if (!is_array($gp)) {
            $gp = [];
        }
        return $gp;
    }
}
