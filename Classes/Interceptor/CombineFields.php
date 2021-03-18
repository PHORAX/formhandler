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
 */
class CombineFields extends AbstractInterceptor
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        if (is_array($this->settings['combineFields.'])) {
            foreach ($this->settings['combineFields.'] as $newField => $options) {
                $newField = str_replace('.', '', $newField);
                if (is_array($options['fields.'])) {
                    $this->gp[$newField] = $this->combineFields($options);
                    $this->utilityFuncs->debugMessage('combined', [$newField, $this->gp[$newField]]);
                }
            }
        }
        return $this->gp;
    }

    /**
     * Combines two or more field values
     *
     * @param array $options TS settings how to perform the combination
     * @return string The combined value
     */
    protected function combineFields($options)
    {
        $separator = ' ';
        if (isset($options['separator'])) {
            $separator = $this->utilityFuncs->getSingle($options, 'separator');
        }
        $fieldsArr = $options['fields.'];
        $combinedString = '';
        $stringsToCombine = [];
        $hideEmptyValues = (int)($this->utilityFuncs->getSingle($options, 'hideEmptyValues'));
        foreach ($fieldsArr as $idx => $field) {
            $value = $this->utilityFuncs->getGlobal($field, $this->gp);
            if ($hideEmptyValues === 0 ||
                ($hideEmptyValues === 1 && strlen($value) > 0)
            ) {
                $stringsToCombine[] = $value;
            }
        }
        if (count($stringsToCombine) > 0) {
            $combinedString = implode($separator, $stringsToCombine);
        }
        return $combinedString;
    }
}
