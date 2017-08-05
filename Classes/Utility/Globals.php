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
 */
class Globals implements SingletonInterface
{
    protected static $ajaxHandler;
    protected static $ajaxMode;
    protected static $cObj;
    protected static $debuggers;
    protected static $formID;
    protected static $formValuesPrefix;
    protected static $gp;
    protected static $langFiles;
    protected static $overrideSettings;
    protected static $predef;
    protected static $randomID;
    protected static $session;
    protected static $settings;
    protected static $submitted;
    protected static $templateCode;
    protected static $templateSuffix;

    public static function setAjaxMode($mode)
    {
        self::$ajaxMode = $mode;
    }

    public static function isAjaxMode()
    {
        return self::$ajaxMode;
    }

    public static function setAjaxHandler($ajaxHandler)
    {
        self::$ajaxHandler = $ajaxHandler;
    }

    public static function setCObj($cObj)
    {
        self::$cObj = $cObj;
    }

    public static function setDebuggers($debuggers)
    {
        self::$debuggers = $debuggers;
    }

    public static function addDebugger($debugger)
    {
        if (!is_array(self::$debuggers)) {
            self::$debuggers = [];
        }
        self::$debuggers[] = $debugger;
    }

    public static function setFormID($formID)
    {
        self::$formID = $formID;
    }

    public static function setFormValuesPrefix($formValuesPrefix)
    {
        self::$formValuesPrefix = $formValuesPrefix;
    }

    public static function setGP($gp)
    {
        self::$gp = $gp;
    }

    public static function setLangFiles($langFiles)
    {
        self::$langFiles = $langFiles;
    }

    public static function setOverrideSettings($overrideSettings)
    {
        self::$overrideSettings = $overrideSettings;
    }

    public static function setPredef($predef)
    {
        self::$predef = $predef;
    }

    public static function setRandomID($randomID)
    {
        self::$randomID = $randomID;
    }

    public static function setSession($session)
    {
        self::$session = $session;
    }

    public static function setSettings($settings)
    {
        self::$settings = $settings;
    }

    public static function setSubmitted($submitted)
    {
        self::$submitted = $submitted;
    }

    public static function setTemplateCode($templateCode)
    {
        self::$templateCode = $templateCode;
    }

    public static function setTemplateSuffix($templateSuffix)
    {
        self::$templateSuffix = $templateSuffix;
    }

    public static function getAjaxHandler()
    {
        return self::$ajaxHandler;
    }

    public static function getCObj()
    {
        return self::$cObj;
    }

    public static function getDebuggers()
    {
        if (!is_array(self::$debuggers)) {
            self::$debuggers = [];
        }
        return self::$debuggers;
    }

    public static function getFormID()
    {
        return self::$formID;
    }

    public static function getFormValuesPrefix()
    {
        return self::$formValuesPrefix;
    }

    public static function getGP()
    {
        if (!is_array(self::$gp)) {
            self::$gp = [];
        }
        return self::$gp;
    }

    public static function getLangFiles()
    {
        if (!is_array(self::$langFiles)) {
            self::$langFiles = [];
        }
        return self::$langFiles;
    }

    public static function getOverrideSettings()
    {
        if (!is_array(self::$overrideSettings)) {
            self::$overrideSettings = [];
        }
        return self::$overrideSettings;
    }

    public static function getPredef()
    {
        return self::$predef;
    }

    public static function getRandomID()
    {
        return self::$randomID;
    }

    public static function getSession()
    {
        return self::$session;
    }

    public static function getSettings()
    {
        if (!is_array(self::$settings)) {
            self::$settings = [];
        }
        return self::$settings;
    }

    public static function isSubmitted()
    {
        return self::$submitted;
    }

    public static function getTemplateCode()
    {
        return self::$templateCode;
    }

    public static function getTemplateSuffix()
    {
        return self::$templateSuffix;
    }
}
