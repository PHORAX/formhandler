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
 * Combines values entered in form field and stores it in a new entry in $this->gp.
 *
 * @author    Reinhard Führicht <rf@typoheads.at>
 */
class TranslateFields extends AbstractInterceptor
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        $this->langFiles = $this->globals->getLangFiles();
        if (is_array($this->settings['translateFields.'])) {
            foreach ($this->settings['translateFields.'] as $newField => $options) {
                $newField = str_replace('.', '', $newField);
                if (isset($options['langKey'])) {
                    $this->gp[$newField] = $this->translateFields($options);
                    $this->utilityFuncs->debugMessage('translated', [$newField, $this->gp[$newField]]);
                }
            }
        }
        return $this->gp;
    }

    /**
     * Searches for a translation of the configured field
     *
     * @param array $options The TS setting for the translation
     * @return string The translated message
     */
    protected function translateFields($options)
    {
        $key = $this->utilityFuncs->getSingle($options, 'langKey');
        $field = $this->utilityFuncs->getSingle($options, 'field');
        if ($field) {
            $key = str_replace('|', $this->gp[$field], $key);
        }
        return $this->utilityFuncs->getTranslatedMessage($this->langFiles, $key);
    }
}
