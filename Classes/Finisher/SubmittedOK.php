<?php
namespace Typoheads\Formhandler\Finisher;

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
 * A finisher showing the content of ###TEMPLATE_SUBMITTEDOK### replacing all common Formhandler markers
 * plus ###PRINT_LINK###, ###PDF_LINK### and ###CSV_LINK###.
 *
 * The finisher sets a flag in session, so that Formhandler will only call this finisher and nothing else if the user reloads the page.
 *
 * A sample configuration looks like this:
 * <code>
 * finishers.3.class = Tx_Formhandler_Finisher_SubmittedOK
 * finishers.3.config.returns = 1
 * finishers.3.config.pdf.class = Tx_Formhandler_Generator_TcPdf
 * finishers.3.config.pdf.exportFields = firstname,lastname,interests,pid,ip,submission_date
 * finishers.3.config.pdf.export2File = 1
 * finishers.3.config.csv.class = Tx_Formhandler_Generator_Csv
 * finishers.3.config.csv.exportFields = firstname,lastname,interests
 * </code>
 */
class SubmittedOK extends AbstractFinisher
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {

        //read template file
        $this->templateFile = $this->globals->getTemplateCode();
        if ($this->settings['templateFile']) {
            $this->templateFile = $this->utilityFuncs->readTemplateFile(false, $this->settings);
        }

        //set view
        $viewClass = '\Typoheads\Formhandler\View\SubmittedOK';
        if ($this->settings['view']) {
            $viewClass = $this->utilityFuncs->getSingle($this->settings, 'view');
        }
        $viewClass = $this->utilityFuncs->prepareClassName($viewClass);
        $view = $this->componentManager->getComponent($viewClass);

        //show TEMPLATE_SUBMITTEDOK
        $view->setTemplate($this->templateFile, ('SUBMITTEDOK' . $this->globals->getTemplateSuffix()));
        if (!$view->hasTemplate()) {
            $view->setTemplate($this->templateFile, 'SUBMITTEDOK');
            if (!$view->hasTemplate()) {
                $this->utilityFuncs->debugMessage('no_submittedok_template', [], 3);
            }
        }

        $view->setSettings($this->globals->getSession()->get('settings'));
        $view->setComponentSettings($this->settings);
        return $view->render($this->gp, []);
    }
}
