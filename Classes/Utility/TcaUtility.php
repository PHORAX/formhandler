<?php
namespace Typoheads\Formhandler\Utility;

/***************************************************************
     *  Copyright notice
     *
     *  (c) 2010 Dev-Team Typoheads (dev@typoheads.at)
     *  All rights reserved
     *
     *  This script is part of the TYPO3 project. The TYPO3 project is
     *  free software; you can redistribute it and/or modify
     *  it under the terms of the GNU General Public License as published by
     *  the Free Software Foundation; either version 2 of the License, or
     *  (at your option) any later version.
     *
     *  The GNU General Public License can be found at
     *  http://www.gnu.org/copyleft/gpl.html.
     *
     *  This script is distributed in the hope that it will be useful,
     *  but WITHOUT ANY WARRANTY; without even the implied warranty of
     *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     *  GNU General Public License for more details.
     *
     *  This copyright notice MUST APPEAR in all copies of the script!
     ***************************************************************/


/**
 * UserFunc for rendering of log entry
 */
class TcaUtility
{
    public function getParams($PA, $fobj)
    {
        $params = unserialize($PA['itemFormElValue']);
        $output =
            '<input
			readonly="readonly" style="display:none"
			name="' . $PA['itemFormElName'] . '"
			value="' . htmlspecialchars($PA['itemFormElValue']) . '"
			onchange="' . htmlspecialchars(implode('', $PA['fieldChangeFunc'])) . '"
			' . $PA['onFocus'] . '/>
		';
        $output .= \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($params);
        return $output;
    }

    /**
     * Adds onchange listener on the drop down menu "predefined".
     * If the event is fired and old value was ".default", then empty some fields.
     *
     * @param array $config
     * @return string the javascript
     * @author Fabien Udriot
     */
    public function addFields_predefinedJS($config)
    {
        $newRecord = 'true';
        if (is_array($GLOBALS['SOBE']->editconf['tt_content']) && reset($GLOBALS['SOBE']->editconf['tt_content']) === 'edit') {
            $newRecord = 'false';
        }

        $uid = null;
        if (is_array($GLOBALS['SOBE']->editconf['tt_content'])) {
            $uid = key($GLOBALS['SOBE']->editconf['tt_content']);
        }
        if ($uid < 0 || empty($uid) || !strstr($uid, 'NEW')) {
            $uid = $GLOBALS['SOBE']->elementsData[0]['uid'];
        }

        $js = "<script>\n";
        $js .= "/*<![CDATA[*/\n";

        $divId = $GLOBALS['SOBE']->tceforms->dynNestedStack[0][1];
        if (!$divId) {
            $divId = "DIV.c-tablayer";
        } else {
            $divId .= "-DIV";
        }
        $js .= "var uid = '" . $uid . "'\n";
        $js .= "var flexformBoxId = '" . $divId . "'\n";
        $js .= "var newRecord = " . $newRecord . "\n";
        $js .= file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('formhandler') . 'Resources/Public/JavaScript/addFields_predefinedJS.js');
        $js .= "/*]]>*/\n";
        $js .= "</script>\n";
        return $js;
    }

    /**
     * Sets the items for the "Predefined" dropdown.
     *
     * @param array $config
     * @return array The config including the items for the dropdown
     */
    public function addFields_predefined($config)
    {
        $pid = false;

        if (is_array($GLOBALS['SOBE']->editconf['tt_content']) && reset($GLOBALS['SOBE']->editconf['tt_content']) === 'new') {
            $pid = key($GLOBALS['SOBE']->editconf['tt_content']);

            //Formhandler inserted after existing content element
            if (intval($pid) < 0) {
                $element = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . abs($pid));
                $pid = $element['pid'];
            }
        }

        $contentUid = $config['row']['uid'] ?: 0;
        if (!$pid) {
            $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . $contentUid);
            if ($row) {
                $pid = $row['pid'];
            }
        }
        $ts = $this->loadTS($pid);

        $predef = [];

        # no config available
        if (!is_array($ts['plugin.']['Tx_Formhandler.']['settings.']['predef.']) || count($ts['plugin.']['Tx_Formhandler.']['settings.']['predef.']) === 0) {
            $optionList[] = [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:be_missing_config'),
                1 => ''
            ];
            return $config['items'] = array_merge($config['items'], $optionList);
        }

        # for each view
        foreach ($ts['plugin.']['Tx_Formhandler.']['settings.']['predef.'] as $key => $view) {
            if (is_array($view)) {
                $beName = $view['name'];
                if (isset($view['name.']['data'])) {
                    $data = explode(':', $view['name.']['data']);
                    if (strtolower($data[0]) === 'lll') {
                        array_shift($data);
                    }
                    $langFileAndKey = implode(':', $data);
                    $beName = $GLOBALS['LANG']->sL('LLL:' . $langFileAndKey);
                }
                if (!$predef[$key]) {
                    $predef[$key] = $beName;
                }
            }
        }

        $optionList = [
            [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:formhandler/Resources/Private/Language/locallang_db.xml:be_please_select'),
                1 => ''
            ]
        ];
        foreach ($predef as $k => $v) {
            $optionList[] = [
                0 => $v,
                1 => $k
            ];
        }
        $config['items'] = array_merge($config['items'], $optionList);
        return $config;
    }

    /**
     * Loads the TypoScript for the current page
     *
     * @param int $pageUid
     * @return array The TypoScript setup
     */
    public function loadTS($pageUid)
    {
        $sysPageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Page\PageRepository');
        $rootLine = $sysPageObj->getRootLine($pageUid);
        $TSObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\ExtendedTemplateService');
        $TSObj->tt_track = 0;
        $TSObj->init();
        $TSObj->runThroughTemplates($rootLine);
        $TSObj->generateConfig();
        return $TSObj->setup;
    }
}
