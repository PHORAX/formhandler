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
 * An interceptor doing XSS checking on GET/POST parameters
 */
class RemoveXSS extends AbstractInterceptor
{

    /**
     * The main method called by the controller
     *
     * @return array The probably modified GET/POST parameters
     */
    public function process()
    {
        $this->removeChars = [];

        //search for a global setting for character removal
        $globalSetting = $this->settings['fieldConf.']['global.'];
        if ($globalSetting['removeChars']) {
            $sep = ',';

            //user set custom rules via cObject
            $cObjSettings = $globalSetting['removeChars.'];
            if (is_array($cObjSettings)) {
                $list = $this->utilityFuncs->getSingle($globalSetting, 'removeChars');

                //user set custom separator
                if ($globalSetting['separator']) {
                    $sep = $this->utilityFuncs->getSingle($globalSetting, 'separator');
                }
            } else {

                //user entered a comma seperated list
                $list = $globalSetting['removeChars'];
            }
            $this->removeChars = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sep, $list);
        } elseif (intval($this->utilityFuncs->getSingle($globalSetting['removeChars.'], 'disable')) === 1) {

            //user disabled removal globally
            $this->removeChars = [];
        }
        $this->gp = $this->sanitizeValues($this->gp);
        return $this->gp;
    }

    /**
     * This method does XSS checks and escapes malicious data
     *
     * @param array $values The GET/POST parameters
     * @return array The sanitized GET/POST parameters
     */
    public function sanitizeValues($values)
    {
        if (!is_array($values)) {
            return [];
        }

        foreach ($values as $key => $value) {
            if (!in_array($key, $this->doNotSanitizeFields) && is_array($value)) {
                $sanitizedArray[$key] = $this->sanitizeValues($value);
            } elseif (!in_array($key, $this->doNotSanitizeFields) && strlen(trim($value)) > 0) {
                $removeChars = $this->removeChars;

                //search for a specific setting for this field
                $fieldSetting = $this->settings['fieldConf.'][$key . '.'];
                if ($fieldSetting['removeChars']) {
                    $sep = ',';

                    //user set custom rules via cObject
                    $cObjSettings = $fieldSetting['removeChars.'];
                    if (is_array($cObjSettings)) {
                        $list = $this->utilityFuncs->getSingle($fieldSetting, 'removeChars');

                        //user set custom separator
                        if ($fieldSetting['separator']) {
                            $sep = $this->utilityFuncs->getSingle($fieldSetting, 'separator');
                        }
                    } else {

                        //user entered a comma seperated list
                        $list = $fieldSetting['removeChars'];
                    }
                    $removeChars = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($sep, $list);
                } elseif (intval($this->utilityFuncs->getSingle($fieldSetting['removeChars.'], 'disable')) === 1) {

                    //user disabled removal for this field
                    $removeChars = [];
                }

                $value = str_replace("\t", '', $value);
                $value = str_replace($removeChars, ' ', $value);

                $isUTF8 = $this->isUTF8($value);
                if (!$isUTF8) {
                    $value = utf8_encode($value);
                }
                $value = htmlspecialchars($value);

                if (!$isUTF8) {
                    $value = utf8_decode($value);
                }
                $sanitizedArray[$key] = $value;
            } else {
                $sanitizedArray[$key] = $value;
            }
        }
        return $sanitizedArray;
    }

    /**
     * This method detects if a given input string if valid UTF-8.
     *
     * @author hmdker <hmdker(at)gmail(dot)com>
     * @param string
     * @return boolean is UTF-8
     */
    protected function isUTF8($str)
    {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }
                if (($i + $bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }
        return true;
    }

    /* (non-PHPdoc)
     * @see Classes/Component/\Typoheads\Formhandler\Component\AbstractComponent#init($gp, $settings)
    */
    public function init($gp, $settings)
    {
        parent::init($gp, $settings);
        $this->doNotSanitizeFields = [];
        if ($this->settings['doNotSanitizeFields']) {
            $this->doNotSanitizeFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->utilityFuncs->getSingle($this->settings, 'doNotSanitizeFields'));
        }
    }
}
