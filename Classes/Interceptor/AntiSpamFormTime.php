<?php
namespace Typoheads\Formhandler\Interceptor;

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
 * Spam protection for the form withouth Captcha.
 * It parses the time the user needs to fill out the form.
 * If the time is below a minimum time or over a maximum time, the submission is treated as Spam.
 * If Spam is detected you can redirect the user to a custom page
 * or use the Subpart ###TEMPLATE_ANTISPAM### to just display something.
 *
 * Example:
 * <code>
 * saveInterceptors.1.class = Tx_Formhandler_Interceptor_AntiSpamFormTime
 *
 * saveInterceptors.1.config.redirectPage = 17
 * saveInterceptors.1.config.minTime.value = 5
 * saveInterceptors.1.config.minTime.unit = seconds
 * saveInterceptors.1.config.maxTime.value = 5
 * saveInterceptors.1.config.maxTime.unit = minutes
 */
class AntiSpamFormTime extends AbstractInterceptor
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        $isSpam = $this->doCheck();
        if ($isSpam) {
            $this->log(true);
            if ($this->settings['redirectPage']) {
                $this->globals->getSession()->reset();
                $this->utilityFuncs->doRedirectBasedOnSettings($this->settings, $this->gp);
                return 'Lousy spammer!';
            } else {

                //set view
                $viewClass = '\Typoheads\Formhandler\View\AntiSpam';
                if ($this->settings['view']) {
                    $viewClass = $this->utilityFuncs->getSingle($this->settings, 'view');
                }
                $viewClass = $this->utilityFuncs->prepareClassName($viewClass);
                $view = $this->componentManager->getComponent($viewClass);
                $view->setLangFiles($this->globals->getLangFiles());
                $view->setPredefined($this->predefined);

                $templateCode = $this->globals->getTemplateCode();
                if ($this->settings['templateFile']) {
                    $templateCode = $this->utilityFuncs->readTemplateFile(false, $this->settings);
                }
                $view->setTemplate($templateCode, 'ANTISPAM');
                if (!$view->hasTemplate()) {
                    $this->utilityFuncs->throwException('spam_detected');
                    return 'Lousy spammer!';
                }
                $content = $view->render($this->gp, []);
                $this->globals->getSession()->reset();
                return $content;
            }
        }
        return $this->gp;
    }

    /**
     * Performs checks if the submitted form should be treated as Spam.
     *
     * @return boolean
     */
    protected function doCheck()
    {
        $value = $this->utilityFuncs->getSingle($this->settings['minTime.'], 'value');
        $unit = $this->utilityFuncs->getSingle($this->settings['minTime.'], 'unit');
        $minTime = $this->utilityFuncs->convertToSeconds($value, $unit);

        $value = $this->utilityFuncs->getSingle($this->settings['maxTime.'], 'value');
        $unit = $this->utilityFuncs->getSingle($this->settings['maxTime.'], 'unit');
        $maxTime = $this->utilityFuncs->convertToSeconds($value, $unit);
        $spam = false;
        if (!isset($this->gp['formtime']) ||
            !is_numeric($this->gp['formtime'])
        ) {
            $spam = true;
        } elseif ($minTime && time() - intval($this->gp['formtime']) < $minTime) {
            $spam = true;
        } elseif ($maxTime && time() - intval($this->gp['formtime']) > $maxTime) {
            $spam = true;
        }
        return $spam;
    }
}
