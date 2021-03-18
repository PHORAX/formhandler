<?php

namespace Typoheads\Formhandler\Interceptor;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * An interceptor parsing some GET/POST parameters
 */
class ParseValues extends AbstractInterceptor
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {

        //parse as float
        $parseFloatFields = $this->utilityFuncs->getSingle($this->settings, 'parseFloatFields');
        $fields = GeneralUtility::trimExplode(',', $parseFloatFields, true);
        $this->parseFloats($fields);

        return $this->gp;
    }

    /**
     * parses the given field values from strings to floats
     *
     * @param array $fields
     */
    protected function parseFloats($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $idx => $field) {
                if (isset($this->gp[$field])) {
                    $this->gp[$field] = $this->getFloat($this->gp[$field]);
                }
            }
        }
    }

    /**
     * Parses the formated value as float. Needed for values like:
     * x xxx,- / xx,xx / xx'xxx,xx / -xx.xxx,xx
     * Caution: This pareses x.xxx.xxx to xxxxxxx (but xx.xx to xx.xx)
     *
     * @param string $value formated float
     *
     * @return float
     */
    protected function getFloat($value)
    {
        return floatval(
            preg_replace_callback(
                '#^([-]*[0-9.,\' ]+?)(([.,])([0-9-]{1,2}))*$#',
                function ($m) {
                    return str_replace(array('.', ',', "'", ' '), '', $m[1]).'.'.$m[4];
                },
                $value
            )
        );
    }
}
