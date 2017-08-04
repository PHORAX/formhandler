<?php
namespace Typoheads\Formhandler\Utility;

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
use TYPO3\CMS\Core\SingletonInterface;

/**
 * A helper class for Formhandler to store global values
 *
 * @author    Reinhard Führicht <rf@typoheads.at>
 */
class Globals implements SingletonInterface
{

    static protected $ajaxHandler;
    static protected $ajaxMode;
    static protected $cObj;
    static protected $debuggers;
    static protected $formID;
    static protected $formValuesPrefix;
    static protected $gp;
    static protected $langFiles;
    static protected $overrideSettings;
    static protected $predef;
    static protected $randomID;
    static protected $session;
    static protected $settings;
    static protected $submitted;
    static protected $templateCode;
    static protected $templateSuffix;

    static public function setAjaxMode($mode)
    {
        self::$ajaxMode = $mode;
    }

    static public function isAjaxMode()
    {
        return self::$ajaxMode;
    }

    static public function setAjaxHandler($ajaxHandler)
    {
        self::$ajaxHandler = $ajaxHandler;
    }

    static public function setCObj($cObj)
    {
        self::$cObj = $cObj;
    }

    static public function setDebuggers($debuggers)
    {
        self::$debuggers = $debuggers;
    }

    static public function addDebugger($debugger)
    {
        if (!is_array(self::$debuggers)) {
            self::$debuggers = [];
        }
        self::$debuggers[] = $debugger;
    }

    static public function setFormID($formID)
    {
        self::$formID = $formID;
    }

    static public function setFormValuesPrefix($formValuesPrefix)
    {
        self::$formValuesPrefix = $formValuesPrefix;
    }

    static public function setGP($gp)
    {
        self::$gp = $gp;
    }

    static public function setLangFiles($langFiles)
    {
        self::$langFiles = $langFiles;
    }

    static public function setOverrideSettings($overrideSettings)
    {
        self::$overrideSettings = $overrideSettings;
    }

    static public function setPredef($predef)
    {
        self::$predef = $predef;
    }

    static public function setRandomID($randomID)
    {
        self::$randomID = $randomID;
    }

    static public function setSession($session)
    {
        self::$session = $session;
    }

    static public function setSettings($settings)
    {
        self::$settings = $settings;
    }

    static public function setSubmitted($submitted)
    {
        self::$submitted = $submitted;
    }

    static public function setTemplateCode($templateCode)
    {
        self::$templateCode = $templateCode;
    }

    static public function setTemplateSuffix($templateSuffix)
    {
        self::$templateSuffix = $templateSuffix;
    }

    static public function getAjaxHandler()
    {
        return self::$ajaxHandler;
    }

    static public function getCObj()
    {
        return self::$cObj;
    }

    static public function getDebuggers()
    {
        if (!is_array(self::$debuggers)) {
            self::$debuggers = [];
        }
        return self::$debuggers;
    }

    static public function getFormID()
    {
        return self::$formID;
    }

    static public function getFormValuesPrefix()
    {
        return self::$formValuesPrefix;
    }

    static public function getGP()
    {
        if (!is_array(self::$gp)) {
            self::$gp = [];
        }
        return self::$gp;
    }

    static public function getLangFiles()
    {
        if (!is_array(self::$langFiles)) {
            self::$langFiles = [];
        }
        return self::$langFiles;
    }

    static public function getOverrideSettings()
    {
        if (!is_array(self::$overrideSettings)) {
            self::$overrideSettings = [];
        }
        return self::$overrideSettings;
    }

    static public function getPredef()
    {
        return self::$predef;
    }

    static public function getRandomID()
    {
        return self::$randomID;
    }

    static public function getSession()
    {
        return self::$session;
    }

    static public function getSettings()
    {
        if (!is_array(self::$settings)) {
            self::$settings = [];
        }
        return self::$settings;
    }

    static public function isSubmitted()
    {
        return self::$submitted;
    }

    static public function getTemplateCode()
    {
        return self::$templateCode;
    }

    static public function getTemplateSuffix()
    {
        return self::$templateSuffix;
    }
}
