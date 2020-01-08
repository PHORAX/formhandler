<?php
namespace Typoheads\Formhandler\View;

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
 * A view for Finisher_SubmittedOK used by Formhandler
 */
class SubmittedOK extends Form
{

    /**
     * This function fills the default markers:
     *
     * ###PRINT_LINK###
     * ###PDF_LINK###
     * ###CSV_LINK###
     *
     * @return string Template with replaced markers
     */
    protected function fillDefaultMarkers()
    {
        parent::fillDefaultMarkers();
        $params = [];
        if ($this->globals->getFormValuesPrefix()) {
            $params[$this->globals->getFormValuesPrefix()] = $this->gp;
        } else {
            $params = $this->gp;
        }
        if ($this->componentSettings['actions.']) {
            foreach ($this->componentSettings['actions.'] as $action => $options) {
                $sanitizedAction = str_replace('.', '', $action);
                $class = $this->utilityFuncs->getPreparedClassName($options);
                if ($class) {
                    $generator = $this->componentManager->getComponent($class);
                    $generator->init($this->gp, $options['config.']);
                    $markers['###' . strtoupper($sanitizedAction) . '_LINK###'] = $generator->getLink($params);
                }
            }
        }
        $this->fillFEUserMarkers($markers);
        $this->fillFileMarkers($markers);
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }
}
