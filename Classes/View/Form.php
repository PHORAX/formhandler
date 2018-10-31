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
 * A default view for Formhandler
 */
class Form extends AbstractView
{

    /**
     * An array of fields to do not encode for output
     *
     * @access protected
     * @var array
     */
    protected $disableEncodingFields;

    /**
     * Main method called by the controller.
     *
     * @param array $gp The current GET/POST parameters
     * @param array $errors The errors occurred in validation
     * @return string content
     */
    public function render($gp, $errors)
    {

        //set GET/POST parameters
        $this->gp = $gp;

        //set template
        $this->template = $this->subparts['template'];
        if (strlen($this->template) === 0) {
            $this->utilityFuncs->throwException('no_template_file');
        }

        $this->errors = $errors;

        //set language file
        if (!$this->langFiles) {
            $this->langFiles = $this->globals->getLangFiles();
        }

        //fill Typoscript markers
        if (is_array($this->settings['markers.'])) {
            $this->fillTypoScriptMarkers();
        }

        //read master template
        if (!$this->masterTemplates) {
            $this->readMasterTemplates();
        }

        if (!empty($this->masterTemplates)) {
            $count = 0;
            while ($count < 5 && preg_match('/###(field|master)_[^#]*###/', $this->template)) {
                $this->replaceMarkersFromMaster();
                $count++;
            }
        }

        if ($this->globals->getAjaxHandler()) {
            $markers = [];
            $this->globals->getAjaxHandler()->fillAjaxMarkers($markers);
            $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
        }

        //fill Typoscript markers
        if (is_array($this->settings['markers.'])) {
            $this->fillTypoScriptMarkers();
        }

        $this->substituteConditionalSubparts('has_translation');
        if (!$this->gp['submitted']) {
            $this->storeStartEndBlock();
        } elseif (intval($this->globals->getSession()->get('currentStep')) !== 1) {
            $this->fillStartEndBlock();
        }

        if (intval($this->settings['fillValueMarkersBeforeLangMarkers']) === 1) {

            //fill value_[fieldname] markers
            $this->fillValueMarkers();
        }

        //fill LLL:[language_key] markers
        $this->fillLangMarkers();

        //substitute ISSET markers
        $this->substituteConditionalSubparts('isset');

        //substitute IF markers
        $this->substituteConditionalSubparts('if');

        //fill default markers
        $this->fillDefaultMarkers();

        if (intval($this->settings['fillValueMarkersBeforeLangMarkers']) !== 1) {

            //fill value_[fieldname] markers
            $this->fillValueMarkers();
        }

        //fill selected_[fieldname]_value markers and checked_[fieldname]_value markers
        $this->fillSelectedMarkers();

        //fill error_[fieldname] markers
        if (!empty($errors)) {
            $this->fillIsErrorMarkers($errors);
            $this->fillErrorMarkers($errors);
        }

        $this->fillIsSuccessMarkers($errors);

        //fill LLL:[language_key] markers again to make language markers in other markers possible
        $this->fillLangMarkers();

        //fill Typoscript markers
        if (is_array($this->settings['markers.'])) {
            $this->fillTypoScriptMarkers();
        }

        //remove markers that were not substituted
        $content = $this->utilityFuncs->removeUnfilledMarkers($this->template);

        if (is_array($this->settings['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $this->settings['stdWrap.']);
        }
        if (intval($this->settings['disableWrapInBaseClass']) !== 1) {
            $content = $this->pi_wrapInBaseClass($content);
        }
        return $content;
    }

    /**
     * Reads the translation file entered in TS setup.
     *
     * @return void
     */
    protected function readMasterTemplates()
    {
        $this->masterTemplates = [];
        if (isset($this->settings['masterTemplateFile']) && !isset($this->settings['masterTemplateFile.'])) {
            array_push($this->masterTemplates, $this->utilityFuncs->resolveRelPathFromSiteRoot($this->settings['masterTemplateFile']));
        } elseif (isset($this->settings['masterTemplateFile']) && isset($this->settings['masterTemplateFile.'])) {
            array_push(
                $this->masterTemplates,
                $this->utilityFuncs->resolveRelPathFromSiteRoot($this->utilityFuncs->getSingle($this->settings, 'masterTemplateFile'))
            );
        } elseif (isset($this->settings['masterTemplateFile.']) && is_array($this->settings['masterTemplateFile.'])) {
            foreach ($this->settings['masterTemplateFile.'] as $key => $masterTemplate) {
                if (false === strpos($key, '.')) {
                    if (is_array($this->settings['masterTemplateFile.'][$key . '.'])) {
                        array_push(
                            $this->masterTemplates,
                            $this->utilityFuncs->resolveRelPathFromSiteRoot($this->utilityFuncs->getSingle($this->settings['masterTemplateFile.'], $key))
                        );
                    } else {
                        array_push($this->masterTemplates, $this->utilityFuncs->resolveRelPathFromSiteRoot($masterTemplate));
                    }
                }
            }
        }
    }

    protected function replaceMarkersFromMaster()
    {
        $fieldMarkers = [];
        foreach ($this->masterTemplates as $idx => $masterTemplate) {
            $masterTemplateCode = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($this->utilityFuncs->resolvePath($masterTemplate));
            $matches = [];
            preg_match_all('/###(field|master)_([^#]*)###/', $masterTemplateCode, $matches);
            if (!empty($matches[0])) {
                $subparts = array_unique($matches[0]);
                $subpartsCodes = [];
                if (is_array($subparts)) {
                    foreach ($subparts as $index => $subpart) {
                        $subpartKey = str_replace('#', '', $subpart);
                        $code = $this->markerBasedTemplateService->getSubpart($masterTemplateCode, $subpart);
                        if (!empty($code)) {
                            $subpartsCodes[$subpartKey] = $code;
                        }
                    }
                }
                foreach ($subpartsCodes as $subpart => $code) {
                    $matchesSlave = [];
                    preg_match_all('/###' . $subpart . '(###|_([^#]*)###)/', $this->template, $matchesSlave);
                    if (!empty($matchesSlave[0])) {
                        foreach ($matchesSlave[0] as $key => $markerName) {
                            $fieldName = $matchesSlave[2][$key];
                            $params = [];
                            if (strpos($fieldName, ';')) {
                                $parts = explode(';', $fieldName);
                                $fieldName = array_shift($parts);
                                $params = explode(',', array_shift($parts));
                            }
                            if ($fieldName) {
                                $markers = [
                                    '###fieldname###' => $fieldName,
                                    '###formValuesPrefix###' => $this->globals->getFormValuesPrefix()
                                ];
                                foreach ($params as $paramKey => $paramValue) {
                                    $markers['###param' . (++$paramKey) . '###'] = $paramValue;
                                }
                                $replacedCode = $this->markerBasedTemplateService->substituteMarkerArray($code, $markers);
                            } else {
                                $replacedCode = $code;
                            }
                            $fieldMarkers[$markerName] = $replacedCode;
                        }
                    }
                }
            }
        }
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $fieldMarkers);
    }

    /**
     * Copies the subparts ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### and stored them in session.
     * This is needed to replace the markers ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### in the next steps.
     *
     * @return void
     */
    protected function storeStartEndBlock()
    {
        $startblock = $this->globals->getSession()->get('startblock');
        $endblock = $this->globals->getSession()->get('endblock');
        if (empty($startblock)) {
            $startblock = $this->markerBasedTemplateService->getSubpart($this->template, '###FORM_STARTBLOCK###');
        }
        if (empty($endblock)) {
            $endblock = $this->markerBasedTemplateService->getSubpart($this->template, '###FORM_ENDBLOCK###');
        }
        $this->globals->getSession()->setMultiple(['startblock' => $startblock, 'endblock' => $endblock]);
    }

    /**
     * Use or remove subparts with [IF|ISSET|HAS_TRANSLATION]_[fieldname]=[value] patterns
     *
     * @author  Arno Dudek <webmaster@adgrafik.at>
     * @author  Reinhard Führicht <rf@typoheads.at>
     * @return    string        substituted HTML content
     */
    protected function substituteConditionalSubparts($type)
    {
        $type = strtolower($type);

        $pattern = '/(\<\!\-\-\s*)?(###' . $type . '_+([^#]*)_*###)([^\-]*\-\-\>)?/i';
        preg_match_all($pattern, $this->template, $matches);
        if (is_array($matches[0])) {
            $resultCount = count($matches[0]);
            for ($i = 0; $i < $resultCount; $i = $i + 2) {
                $conditionString = $matches[3][$i];
                $fullMarkerName = $matches[0][$i];
                $fullEndMarker = $matches[0][$i + 1];
                $conditions = preg_split('/\s*(\|\||&&)\s*/i', $conditionString, -1, PREG_SPLIT_DELIM_CAPTURE);
                $operator = null;
                $finalConditionResult = false;
                $count = 0;

                foreach ($conditions as $condition) {
                    if ($condition === '||' || $condition === '&&') {
                        $operator = $condition;
                    } else {
                        switch ($type) {
                            case 'if':
                                $conditionResult = $this->handleIfSubpartCondition($condition);
                                break;
                            case 'isset':
                                $conditionResult = $this->handleIssetSubpartCondition($condition);
                                break;
                            case 'has_translation':
                                $conditionResult = $this->handleHasTranslationSubpartCondition($condition);
                                break;
                            default:
                                $this->utilityFuncs->throwException('Unsupported conditional subpart type: ' . $type);
                                break;
                        }
                    }
                    if ($count === 0) {
                        $finalConditionResult = $conditionResult;
                    } elseif ($operator === '&&') {
                        $finalConditionResult = ($finalConditionResult && $conditionResult);
                    } elseif ($operator === '||') {
                        $finalConditionResult = ($finalConditionResult || $conditionResult);
                    } else {
                        $finalConditionResult = $conditionResult;
                    }
                    $count++;
                }
                $write = (boolean)$finalConditionResult;
                $replacement = '';
                if ($write) {
                    $replacement = '${1}';
                }
                $fullMarkerName = preg_quote($fullMarkerName, '/');
                $fullEndMarker = preg_quote($fullEndMarker, '/');
                $pattern = '/' . $fullMarkerName . '(.*?)' . $fullEndMarker . '/ism';
                $this->template = preg_replace($pattern, $replacement, $this->template);
            }
        }
    }

    protected function handleIssetSubpartCondition($condition)
    {
        $fieldname = $condition;
        $negate = false;
        if (substr($condition, 0, 1) === '!') {
            $fieldname = substr($condition, 1);
            $negate = true;
        }
        $value = $this->utilityFuncs->getGlobal($fieldname, $this->gp);
        if (is_array($value)) {
            $result = (!empty($value));
        } else {
            $result = (strlen(trim($value)) > 0);
        }
        if ($negate) {
            $result = !$result;
        }
        return $result;
    }

    protected function handleHasTranslationSubpartCondition($condition)
    {
        $translation = $this->utilityFuncs->getTranslatedMessage($this->langFiles, $condition);
        return (strlen($translation) > 0);
    }

    protected function handleIfSubpartCondition($condition)
    {
        return $this->utilityFuncs->getConditionResult($condition, $this->gp);
    }

    /**
     * Fills the markers ###FORM_STARTBLOCK### and ###FORM_ENDBLOCK### with the stored values from session.
     *
     * @return void
     */
    protected function fillStartEndBlock()
    {
        $markers = [
            '###FORM_STARTBLOCK###' => $this->globals->getSession()->get('startblock'),
            '###FORM_ENDBLOCK###' => $this->globals->getSession()->get('endblock')
        ];
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    /**
     * Returns the global TypoScript settings of Formhandler
     *
     * @return array The settings
     */
    protected function parseSettings()
    {
        return $this->globals->getSession()->get('settings');
    }

    /**
     * Substitutes markers
     *        ###selected_[fieldname]_[value]###
     *        ###checked_[fieldname]_[value]###
     * in $this->template
     *
     * @return void
     */
    protected function fillSelectedMarkers()
    {
        $values = $this->gp;
        unset($values['randomID']);
        unset($values['submitted']);
        unset($values['removeFile']);
        unset($values['removeFileField']);
        unset($values['submitField']);
        unset($values['formErrors']);
        $markers = $this->getSelectedMarkers($values);
        $markers = array_merge($markers, $this->getSelectedMarkers($this->gp, 0, 'checked_'));
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);

        $this->template = preg_replace('/###(selected|checked)_.*?###/i', '', $this->template);
    }

    /**
     * Substitutes default markers in $this->template.
     *
     * @return void
     */
    protected function fillDefaultMarkers()
    {
        $parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
        if (isset($parameters['id'])) {
            unset($parameters['id']);
        }
        if (isset($parameters['eID'])) {
            unset($parameters['eID']);
        }
        if (isset($parameters['randomID'])) {
            unset($parameters['randomID']);
        }

        $path = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', $parameters);
        $path = preg_replace('/ADMCMD_[^=]+=[^&]+(&)?/', '', $path);
        $path = htmlspecialchars($path);
        $markers = [];
        $markers['###REL_URL###'] = $path;
        $markers['###TIMESTAMP###'] = time();

        //Calculate timestamp only once to prevent false positives when a small error in the form gets corrected fast.
        if (strlen(trim($this->gp['formtime']))) {
            $markers['###TIMESTAMP###'] = htmlspecialchars($this->gp['formtime']);
        }
        $markers['###RANDOM_ID###'] = htmlspecialchars($this->gp['randomID']);
        $markers['###ABS_URL###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($path);
        $markers['###rel_url###'] = $markers['###REL_URL###'];
        $markers['###timestamp###'] = $markers['###TIMESTAMP###'];
        $markers['###abs_url###'] = $markers['###ABS_URL###'];

        $markers['###formID###'] = htmlspecialchars($this->globals->getFormID());

        $name = 'submitted';
        if ($this->globals->getFormValuesPrefix()) {
            $name = $this->globals->getFormValuesPrefix() . '[submitted]';
        }
        $markers['###HIDDEN_FIELDS###'] = '
			<input type="hidden" name="id" value="' . $GLOBALS['TSFE']->id . '" />
			<input type="hidden" name="' . htmlspecialchars($name) . '" value="1" />
		';

        $name = 'randomID';
        if ($this->globals->getFormValuesPrefix()) {
            $name = $this->globals->getFormValuesPrefix() . '[randomID]';
        }
        $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($this->gp['randomID']) . '" />
		';

        $name = 'removeFile';
        if ($this->globals->getFormValuesPrefix()) {
            $name = $this->globals->getFormValuesPrefix() . '[removeFile]';
        }
        $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" id="removeFile-' . htmlspecialchars($this->gp['randomID']) . '" name="' . htmlspecialchars($name) . '" value="" />
		';

        $name = 'removeFileField';
        if ($this->globals->getFormValuesPrefix()) {
            $name = $this->globals->getFormValuesPrefix() . '[removeFileField]';
        }
        $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" id="removeFileField-' . htmlspecialchars($this->gp['randomID']) . '" name="' . htmlspecialchars($name) . '" value="" />
		';

        $name = 'submitField';
        if ($this->globals->getFormValuesPrefix()) {
            $name = $this->globals->getFormValuesPrefix() . '[submitField]';
        }
        $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" id="submitField-' . htmlspecialchars($this->gp['randomID']) . '" name="' . htmlspecialchars($name) . '" value="" />
		';

        $name = 'formToken';
        if ($this->globals->getFormValuesPrefix()) {
            $name = $this->globals->getFormValuesPrefix() . '[formToken]';
        }
        if ($this->gp['formToken']) {
            $markers['###HIDDEN_FIELDS###'] .= '
				<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($this->gp['formToken']) . '" />
			';
        }

        if (is_array($this->settings['session.'])
            && intval($this->utilityFuncs->getSingle($this->settings['session.']['config.'], 'disableCookies')) === 1
            && intval(ini_get('session.use_trans_sid')) === 0
        ) {

            /*
             * User currently does not have a session cookie and php is not configured to
             * automatically add session info to forms and links
             */
            $markers['###HIDDEN_FIELDS###'] .= '
				<input type="hidden" name="' . session_name() . '" value="' . session_id() . '" />
			';
        }
        $currentStepFromSession = $this->globals->getSession()->get('currentStep');
        $hiddenActionFieldName = 'step-';
        $prefix = $this->globals->getFormValuesPrefix();
        if ($prefix) {
            $hiddenActionFieldName = $prefix . '[' . $hiddenActionFieldName . '#step#-#action#]';
        } else {
            $hiddenActionFieldName = $hiddenActionFieldName . '#step#-#action#';
        }

        // submit name for next page
        $hiddenActionFieldName = ' name="' . str_replace('#action#', 'next', $hiddenActionFieldName) . '" ';
        $hiddenActionFieldName = str_replace('#step#', $currentStepFromSession + 1, $hiddenActionFieldName);

        $markers['###HIDDEN_FIELDS###'] .= '
			<input type="hidden" ' . $hiddenActionFieldName . ' id="ieHiddenField-' . htmlspecialchars($this->gp['randomID']) . '" value="1" />
		';

        $markers['###formValuesPrefix###'] = $this->globals->getFormValuesPrefix();

        if ($this->gp['generated_authCode']) {
            $markers['###auth_code###'] = htmlspecialchars($this->gp['generated_authCode']);
        }

        $markers['###ip###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $markers['###IP###'] = $markers['###ip###'];
        $markers['###submission_date###'] = date('d.m.Y H:i:s', time());
        $markers['###pid###'] = $GLOBALS['TSFE']->id;
        $markers['###PID###'] = $markers['###pid###'];

        // current step
        $markers['###curStep###'] = $currentStepFromSession;

        // maximum step/number of steps
        $markers['###maxStep###'] = $this->globals->getSession()->get('totalSteps');

        // the last step shown
        $markers['###lastStep###'] = $this->globals->getSession()->get('lastStep');

        $name = 'step-';
        $prefix = $this->globals->getFormValuesPrefix();
        if ($prefix) {
            $name = $prefix . '[' . $name . '#step#-#action#]';
        } else {
            $name = $name . '#step#-#action#';
        }

        // submit name for next page
        $nextName = ' name="' . str_replace('#action#', 'next', $name) . '" ';
        $nextName = str_replace('#step#', $currentStepFromSession + 1, $nextName);
        $markers['###submit_nextStep###'] = $nextName;

        // submit name for previous page
        $prevName = ' name="' . str_replace('#action#', 'prev', $name) . '" ';
        $allowStepJumps = false;
        if (isset($this->settings['allowStepJumps'])) {
            $allowStepJumps = (bool)$this->utilityFuncs->getSingle($this->settings, 'allowStepJumps');
        }
        $previousStep = $currentStepFromSession - 1;
        if ($allowStepJumps && $this->globals->getSession()->get('lastStep') < $currentStepFromSession) {
            $previousStep = $this->globals->getSession()->get('lastStep');
        }
        if ($previousStep < 1) {
            $previousStep = 1;
        }
        $prevName = str_replace('#step#', $previousStep, $prevName);
        $markers['###submit_prevStep###'] = $prevName;

        // submits for next/prev steps with template suffix
        preg_match_all('/###submit_nextStep_[^#]+?###/Ssm', $this->template, $allNextSubmits);
        foreach ($allNextSubmits[0] as $nextSubmitSuffix) {
            $nextSubmitSuffix = substr($nextSubmitSuffix, 19, -3);
            $nextName = ' name="' . str_replace('#action#', 'next', $name) . '[' . $nextSubmitSuffix . ']" ';
            $nextName = str_replace('#step#', $currentStepFromSession + 1, $nextName);
            $markers['###submit_nextStep_' . $nextSubmitSuffix . '###'] = $nextName;
        }

        preg_match_all('/###submit_prevStep_[^#]+?###/Ssm', $this->template, $allPrevSubmits);
        foreach ($allPrevSubmits[0] as $prevSubmitSuffix) {
            $prevSubmitSuffix = substr($prevSubmitSuffix, 19, -3);
            $prevName = ' name="' . str_replace('#action#', 'prev', $name) . '[' . $prevSubmitSuffix . ']" ';
            $prevName = str_replace('#step#', $currentStepFromSession + 1, $prevName);
            $markers['###submit_prevStep_' . $prevSubmitSuffix . '###'] = $prevName;
        }

        // submit name for reloading the same page/step
        $reloadName = ' name="' . str_replace('#action#', 'reload', $name) . '" ';
        $reloadName = str_replace('#step#', $currentStepFromSession, $reloadName);
        $markers['###submit_reload###'] = $reloadName;

        preg_match_all('/###submit_step_([^#])+?###/Ssm', $this->template, $allJumpToStepSubmits);
        foreach ($allJumpToStepSubmits[0] as $idx => $allJumpToStepSubmit) {
            $step = intval($allJumpToStepSubmits[1][$idx]);
            $action = 'next';
            if ($step < $this->currentStep) {
                $action = 'prev';
            }
            $submitName = ' name="' . str_replace('#action#', $action, $name) . '" ';
            $submitName = str_replace('#step#', $step, $submitName);
            $markers['###submit_step_' . $step . '###'] = $submitName;
        }

        // step bar
        $prevName = str_replace('#action#', 'prev', $name);
        $prevName = str_replace('#step#', $currentStepFromSession - 1, $prevName);
        $nextName = str_replace('#action#', 'next', $name);
        $nextName = str_replace('#step#', $currentStepFromSession + 1, $nextName);
        $markers['###step_bar###'] = $this->createStepBar(
            $currentStepFromSession,
            $this->globals->getSession()->get('totalSteps'),
            $prevName,
            $nextName
        );

        $this->fillCaptchaMarkers($markers);
        $this->fillFEUserMarkers($markers);
        $this->fillFileMarkers($markers);

        if (!strstr($this->template, '###HIDDEN_FIELDS###')) {
            $this->template = preg_replace(
                '/(<form[^>]*>)/i',
                '$1<fieldset style="display: none;">' . $markers['###HIDDEN_FIELDS###'] . '</fieldset>',
                $this->template
            );
        }

        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    /**
     * Fills the markers for the supported captcha extensions.
     *
     * @param array &$markers Reference to the markers array
     * @return void
     */
    protected function fillCaptchaMarkers(&$markers)
    {
        if (stristr($this->template, '###CAPTCHA###') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('captcha')) {
            $markers['###CAPTCHA###'] = \ThinkopenAt\Captcha\Utility::makeCaptcha();
            $markers['###captcha###'] = $markers['###CAPTCHA###'];
        }
        if (stristr($this->template, '###SR_FREECAP_IMAGE###') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sr_freecap')) {
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sr_freecap') . 'pi2/class.tx_srfreecap_pi2.php');
            $this->freeCap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_srfreecap_pi2');
            $markers = array_merge($markers, $this->freeCap->makeCaptcha());
        }
        if (stristr($this->template, '###RECAPTCHA###') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('jm_recaptcha')) {
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('jm_recaptcha') . 'class.tx_jmrecaptcha.php');
            $this->recaptcha = new \tx_jmrecaptcha();
            $markers['###RECAPTCHA###'] = $this->recaptcha->getReCaptcha();
            $markers['###recaptcha###'] = $markers['###RECAPTCHA###'];
        }

        if (stristr($this->template, '###WT_CALCULATING_CAPTCHA###') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('wt_calculating_captcha')) {
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wt_calculating_captcha') . 'class.tx_wtcalculatingcaptcha.php');

            $captcha = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wtcalculatingcaptcha');
            $markers['###WT_CALCULATING_CAPTCHA###'] = $captcha->generateCaptcha();
            $markers['###wt_calculating_captcha###'] = $markers['###WT_CALCULATING_CAPTCHA###'];
        }

        if (stristr($this->template, '###MATHGUARD###') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('mathguard')) {
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mathguard') . 'class.tx_mathguard.php');

            $captcha = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mathguard');
            $markers['###MATHGUARD###'] = $captcha->getCaptcha();
            $markers['###mathguard###'] = $markers['###MATHGUARD###'];
        }
    }

    /**
     * Fills the markers ###FEUSER_[property]### with the data from $GLOBALS["TSFE"]->fe_user->user.
     *
     * @param array &$markers Reference to the markers array
     * @return void
     */
    protected function fillFEUserMarkers(&$markers)
    {
        if (is_array($GLOBALS["TSFE"]->fe_user->user)) {
            foreach ($GLOBALS["TSFE"]->fe_user->user as $k => $v) {
                $markers['###FEUSER_' . strtoupper($k) . '###'] = $v;
                $markers['###FEUSER_' . strtolower($k) . '###'] = $v;
                $markers['###feuser_' . strtoupper($k) . '###'] = $v;
                $markers['###feuser_' . strtolower($k) . '###'] = $v;
            }
        }
    }

    /**
     * Fills the file specific markers:
     *
     *  ###[fieldname]_minSize###
     *  ###[fieldname]_maxSize###
     *  ###[fieldname]_allowedTypes###
     *  ###[fieldname]_maxCount###
     *  ###[fieldname]_fileCount###
     *  ###[fieldname]_remainingCount###
     *
     *  ###[fieldname]_uploadedFiles###
     *  ###total_uploadedFiles###
     *
     * @param array &$markers Reference to the markers array
     * @return void
     */
    public function fillFileMarkers(&$markers)
    {
        $settings = $this->parseSettings();

        $flexformValue = $this->utilityFuncs->pi_getFFvalue($this->cObj->data['pi_flexform'], 'required_fields', 'sMISC');
        if ($flexformValue) {
            $fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $flexformValue);
            if (is_array($settings['validators.'])) {

                // Searches the index of Tx_Formhandler_Validator_Default
                foreach ($settings['validators.'] as $index => $validator) {
                    $currentValidatorClass = $this->utilityFuncs->getPreparedClassName($validator);
                    if ($currentValidatorClass === 'Tx_Formhandler_Validator_Default') {
                        break;
                    }
                }
            } else {
                $index = 1;
            }

            // Adds the value.
            foreach ($fields as $idx => $field) {
                $settings['validators.'][$index . '.']['config.']['fieldConf.'][$field . '.']['errorCheck.'] = [];
                $settings['validators.'][$index . '.']['config.']['fieldConf.'][$field . '.']['errorCheck.']['1'] = 'required';
            }
        }

        $sessionFiles = $this->globals->getSession()->get('files');

        $requiredSign = $this->utilityFuncs->getSingle($settings, 'requiredSign');
        if (strlen($requiredSign) === 0) {
            $requiredSign = '*';
        }
        $requiredMarker = $this->utilityFuncs->getSingle($settings, 'requiredMarker');

        //parse validation settings
        if (is_array($settings['validators.'])) {
            if (intval($this->utilityFuncs->getSingle($settings['validators.'], 'disable')) === 0) {
                foreach ($settings['validators.'] as $key => $validatorSettings) {
                    if (intval($this->utilityFuncs->getSingle($validatorSettings, 'disable')) === 0) {
                        $disableErrorCheckFields = [];
                        if (is_array($validatorSettings['config.']) && isset($validatorSettings['config.']['disableErrorCheckFields'])) {
                            $disableErrorCheckFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $validatorSettings['config.']['disableErrorCheckFields']);
                        }
                        if (is_array($validatorSettings['config.']) && is_array($validatorSettings['config.']['fieldConf.'])) {
                            foreach ($validatorSettings['config.']['fieldConf.'] as $fieldname => $fieldSettings) {
                                $replacedFieldname = str_replace('.', '', $fieldname);
                                if (is_array($fieldSettings['errorCheck.'])) {
                                    foreach ($fieldSettings['errorCheck.'] as $key => $check) {
                                        switch ($check) {
                                            case 'fileMinSize':
                                                $minSize = $fieldSettings['errorCheck.'][$key . '.']['minSize'];
                                                $markers['###' . $replacedFieldname . '_minSize###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($minSize, ' Bytes| KB| MB| GB');
                                                break;
                                            case 'fileMaxSize':
                                                $maxSize = $fieldSettings['errorCheck.'][$key . '.']['maxSize'];
                                                $markers['###' . $replacedFieldname . '_maxSize###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($maxSize, ' Bytes| KB| MB| GB');
                                                break;
                                            case 'fileAllowedTypes':
                                                $types = $fieldSettings['errorCheck.'][$key . '.']['allowedTypes'];
                                                $markers['###' . $replacedFieldname . '_allowedTypes###'] = $types;
                                                break;
                                            case 'fileMaxCount':
                                                $maxCount = $fieldSettings['errorCheck.'][$key . '.']['maxCount'];
                                                $markers['###' . $replacedFieldname . '_maxCount###'] = $maxCount;

                                                $fileCount = count($sessionFiles[$replacedFieldname]);
                                                $markers['###' . $replacedFieldname . '_fileCount###'] = $fileCount;

                                                $remaining = $maxCount - $fileCount;
                                                $markers['###' . $replacedFieldname . '_remainingCount###'] = $remaining;
                                                break;
                                            case 'fileMinCount':
                                                $minCount = $fieldSettings['errorCheck.'][$key . '.']['minCount'];
                                                $markers['###' . $replacedFieldname . '_minCount###'] = $minCount;
                                                break;
                                            case 'fileMaxTotalSize':
                                                $maxTotalSize = $fieldSettings['errorCheck.'][$key . '.']['maxTotalSize'];
                                                $markers['###' . $replacedFieldname . '_maxTotalSize###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($maxTotalSize, ' Bytes| KB| MB| GB');
                                                $totalSize = 0;
                                                if (is_array($sessionFiles[$replacedFieldname])) {
                                                    foreach ($sessionFiles[$replacedFieldname] as $file) {
                                                        $totalSize += intval($file['size']);
                                                    }
                                                }
                                                $markers['###' . $replacedFieldname . '_currentTotalSize###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($totalSize, ' Bytes| KB| MB| GB');
                                                $markers['###' . $replacedFieldname . '_remainingTotalSize###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($maxTotalSize - $totalSize, ' Bytes| KB| MB| GB');
                                                break;
                                            case 'required':
                                            case 'fileRequired':
                                            case 'jmRecaptcha':
                                            case 'captcha':
                                            case 'srFreecap':
                                            case 'mathGuard':
                                                if (!in_array('all', $disableErrorCheckFields) && !in_array($replacedFieldname, $disableErrorCheckFields)) {
                                                    $markers['###required_' . $replacedFieldname . '###'] = $requiredSign;
                                                    $markers['###requiredMarker_' . $replacedFieldname . '###'] = $requiredMarker;
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (is_array($sessionFiles)) {
            foreach ($sessionFiles as $field => $files) {
                foreach ($files as $idx => $fileInfo) {
                    $filename = $fileInfo['name'];
                    $thumb = '';
                    if (intval($settings['singleFileMarkerTemplate.']['showThumbnails']) === 1 || intval($settings['singleFileMarkerTemplate.']['showThumbnails']) === 2) {
                        $imgConf['image.'] = $settings['singleFileMarkerTemplate.']['image.'];
                        $thumb = $this->getThumbnail($imgConf, $fileInfo);
                    }
                    $text = $this->utilityFuncs->getSingle($settings['files.'], 'customRemovalText');
                    if (strlen($text) === 0) {
                        $text = 'X';
                    }
                    $link = '';
                    $uploadedFileName = $fileInfo['uploaded_name'];
                    if (!$uploadedFileName) {
                        $uploadedFileName = $fileInfo['name'];
                    }
                    if ($this->globals->getAjaxHandler() && $settings['files.']['enableAjaxFileRemoval']) {
                        $link = $this->globals->getAjaxHandler()->getFileRemovalLink($text, $field, $uploadedFileName);
                    } elseif ($settings['files.']['enableFileRemoval']) {
                        $submitName = 'step-' . $this->globals->getSession()->get('currentStep') . '-reload';
                        if ($this->globals->getFormValuesPrefix()) {
                            $submitName = $this->globals->getFormValuesPrefix() . '[' . $submitName . ']';
                        }
                        $onClick = "
							document.getElementById('removeFile-" . $this->globals->getRandomID() . "').value='" . $uploadedFileName . "';
							document.getElementById('removeFileField-" . $this->globals->getRandomID() . "').value='" . $field . "';
							document.getElementById('submitField-" . $this->globals->getRandomID() . "').name='" . $submitName . "';
							document.getElementById('ieHiddenField-" . $this->globals->getRandomID() . "').name='dummy';
						";

                        if ($this->globals->getFormID()) {
                            $onClick .= "document.getElementById('" . $this->globals->getFormID() . "').submit();";
                        } else {
                            $onClick .= 'document.forms[0].submit();';
                        }

                        $onClick .= 'return false;';

                        $link = '<a
								href="javascript:void(0)" 
								class="formhandler_removelink" 
								onclick="' . str_replace(["\n", '	'], '', $onClick) . '"
								>' . $text . '</a>';
                    }
                    $stdWrappedFilename = $this->utilityFuncs->wrap($filename, $this->settings['singleFileMarkerTemplate.'], 'filenameWrap');

                    $wrappedFilename = $this->utilityFuncs->wrap($stdWrappedFilename . $link, $settings['singleFileMarkerTemplate.'], 'singleWrap');
                    $wrappedThumb = $this->utilityFuncs->wrap($thumb . $link, $settings['singleFileMarkerTemplate.'], 'singleWrap');
                    $wrappedThumbFilename = $this->utilityFuncs->wrap($thumb . ' ' . $stdWrappedFilename . $link, $settings['singleFileMarkerTemplate.'], 'singleWrap');
                    if (intval($settings['singleFileMarkerTemplate.']['showThumbnails']) === 1) {
                        $markers['###' . $field . '_uploadedFiles###'] .= $wrappedThumb;
                    } elseif (intval($settings['singleFileMarkerTemplate.']['showThumbnails']) === 2) {
                        $markers['###' . $field . '_uploadedFiles###'] .= $wrappedThumbFilename;
                    } else {
                        $markers['###' . $field . '_uploadedFiles###'] .= $wrappedFilename;
                    }
                    if (intval($settings['totalFilesMarkerTemplate.']['showThumbnails']) === 1 || intval($settings['totalFilesMarkerTemplate.']['showThumbnails']) === 2) {
                        $imgConf['image.'] = $settings['totalFilesMarkerTemplate.']['image.'];
                        if (!$imgConf['image.']) {
                            $imgConf['image.'] = $settings['singleFileMarkerTemplate.']['image.'];
                        }
                        $thumb = $this->getThumbnail($imgConf, $fileInfo);
                    }
                    $stdWrappedFilename = $this->utilityFuncs->wrap($filename, $this->settings['totalFilesMarkerTemplate.'], 'filenameWrap');

                    $wrappedFilename = $this->utilityFuncs->wrap($stdWrappedFilename . $link, $settings['totalFilesMarkerTemplate.'], 'singleWrap');
                    $wrappedThumb = $this->utilityFuncs->wrap($thumb . $link, $settings['totalFilesMarkerTemplate.'], 'singleWrap');
                    $wrappedThumbFilename = $this->utilityFuncs->wrap($thumb . ' ' . $stdWrappedFilename . $link, $settings['totalFilesMarkerTemplate.'], 'singleWrap');

                    if (intval($settings['totalFilesMarkerTemplate.']['showThumbnails']) === 1) {
                        $markers['###total_uploadedFiles###'] .= $wrappedThumb;
                    } elseif (intval($settings['totalFilesMarkerTemplate.']['showThumbnails']) === 2) {
                        $markers['###total_uploadedFiles###'] .= $wrappedThumbFilename;
                    } else {
                        $markers['###total_uploadedFiles###'] .= $wrappedFilename;
                    }
                }
                $markers['###' . $field . '_uploadedFiles###'] = $this->utilityFuncs->wrap($markers['###' . $field . '_uploadedFiles###'], $settings['singleFileMarkerTemplate.'], 'totalWrap');
                $markers['###' . $field . '_uploadedFiles###'] = '<div id="Tx_Formhandler_UploadedFiles_' . $field . '">' . $markers['###' . $field . '_uploadedFiles###'] . '</div>';
            }
            $markers['###total_uploadedFiles###'] = $this->utilityFuncs->wrap($markers['###total_uploadedFiles###'], $settings['totalFilesMarkerTemplate.'], 'totalWrap');
            $markers['###TOTAL_UPLOADEDFILES###'] = $markers['###total_uploadedFiles###'];
            $markers['###total_uploadedfiles###'] = $markers['###total_uploadedFiles###'];
        }

        $requiredSign = $this->utilityFuncs->getSingle($settings, 'requiredSign');
        if (strlen($requiredSign) === 0) {
            $requiredSign = '*';
        }
        $markers['###required###'] = $requiredSign;
        $markers['###REQUIRED###'] = $markers['###required###'];
    }

    protected function getThumbnail(&$imgConf, &$fileInfo)
    {
        $filename = $fileInfo['name'];
        $imgConf['image'] = 'IMAGE';
        if (!$imgConf['image.']['altText']) {
            $imgConf['image.']['altText'] = $filename;
        }
        if (!$imgConf['image.']['titleText']) {
            $imgConf['image.']['titleText'] = $filename;
        }
        $relPath = substr(($fileInfo['uploaded_folder'] . $fileInfo['uploaded_name']), 1);

        $imgConf['image.']['file'] = $relPath;
        if (!$imgConf['image.']['file.']['width'] && !$imgConf['image.']['file.']['height']) {
            $imgConf['image.']['file.']['width'] = '100m';
            $imgConf['image.']['file.']['height'] = '100m';
        }
        $thumb = $this->cObj->IMAGE($imgConf['image.']);
        return $thumb;
    }

    /**
     * Substitutes markers
     *        ###is_error_[fieldname]###
     *        ###is_error###
     * in $this->template
     *
     * @param array $errors
     * @return void
     */
    protected function fillIsErrorMarkers($errors)
    {
        $markers = [];
        foreach ($errors as $field => $types) {
            if ($this->settings['isErrorMarker.'][$field]) {
                $errorMessage = $this->utilityFuncs->getSingle($this->settings['isErrorMarker.'], $field);
            } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_error_' . $field))) > 0) {
                $errorMessage = $temp;
            } elseif ($this->settings['isErrorMarker.']['default']) {
                $errorMessage = $this->utilityFuncs->getSingle($this->settings['isErrorMarker.'], 'default');
            } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_error_default'))) > 0) {
                $errorMessage = $temp;
            }
            $markers['###is_error_' . $field . '###'] = $errorMessage;
        }
        if ($this->settings['isErrorMarker.']['global']) {
            $errorMessage = $this->utilityFuncs->getSingle($this->settings['isErrorMarker.'], 'global');
        } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_error'))) > 0) {
            $errorMessage = $temp;
        }
        $markers['###is_error###'] = $errorMessage;
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    /**
     * Substitutes markers
     *        ###is_success_[fieldname]###
     * in $this->template
     *
     * @param array $errors
     * @return void
     */
    protected function fillIsSuccessMarkers($errors)
    {
        $markers = [];
        foreach ($this->gp as $field => $value) {
            if (!isset($errors[$field])) {
                if ($this->settings['isSuccessMarker.'][$field]) {
                    $successMessage = $this->utilityFuncs->getSingle($this->settings['isSuccessMarker.'], $field);
                } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_success_' . $field))) > 0) {
                    $successMessage = $temp;
                } elseif ($this->settings['isSuccessMarker.']['default']) {
                    $successMessage = $this->utilityFuncs->getSingle($this->settings['isSuccessMarker.'], 'default');
                } elseif (strlen($temp = trim($this->utilityFuncs->getTranslatedMessage($this->langFiles, 'is_success_default'))) > 0) {
                    $successMessage = $temp;
                }
                $markers['###is_success_' . $field . '###'] = $successMessage;
            }
        }
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    /**
     * Substitutes markers
     *        ###error_[fieldname]###
     *        ###ERROR###
     * in $this->template
     *
     * @return void
     */
    protected function fillErrorMarkers(&$errors)
    {
        $markers = [];
        foreach ($errors as $field => $types) {
            $errorMessages = [];
            $temp = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_' . $field);
            if (strlen($temp) > 0) {
                $errorMessage = $this->utilityFuncs->wrap($temp, $this->settings['singleErrorTemplate.'], 'singleWrap');
                $errorMessages[] = $errorMessage;
            }
            if (!is_array($types)) {
                $types = [$types];
            }
            foreach ($types as $idx => $type) {
                $temp = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $type);
                $type = array_shift($temp);
                foreach ($temp as $subIdx => $item) {
                    $item = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('::', $item);
                    $values[$item[0]] = $item[1];
                }

                //try to load specific error message with key like error_fieldname_integer
                $errorMessage = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_' . $field . '_' . $type);
                if (strlen($errorMessage) === 0) {
                    $type = strtolower($type);
                    $errorMessage = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_' . $field . '_' . $type);
                }
                //Still no error message found, try to find a less specific one
                if (strlen($errorMessage) === 0) {
                    $type = strtolower($type);
                    $errorMessage = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'error_default_' . $type);
                }
                if ($errorMessage) {
                    $errorMessage = str_replace(['###fieldname###', '###FIELDNAME###'], $field, $errorMessage);
                    if (is_array($values)) {
                        foreach ($values as $key => $value) {
                            $errorMessage = str_replace('###' . $key . '###', $value, $errorMessage);
                        }
                    }
                    $errorMessage = $this->utilityFuncs->wrap($errorMessage, $this->settings['singleErrorTemplate.'], 'singleWrap');
                    $errorMessages[] = $errorMessage;
                } else {
                    $this->utilityFuncs->debugMessage('no_error_message', ['error_' . $field . '_' . $type], 2);
                }
            }
            $errorMessage = implode('', $errorMessages);
            $errorMessage = $this->utilityFuncs->wrap($errorMessage, $this->settings['singleErrorTemplate.'], 'totalWrap');
            $clearErrorMessage = $errorMessage;
            if ($this->settings['addErrorAnchors']) {
                $errorMessage = '<a name="' . $field . '-' . $this->globals->getRandomID() . '">' . $errorMessage . '</a>';
            }
            $langMarkers = $this->utilityFuncs->getFilledLangMarkers($errorMessage, $this->langFiles);
            $errorMessage = $this->markerBasedTemplateService->substituteMarkerArray($errorMessage, $langMarkers);
            $markers['###error_' . $field . '###'] = $errorMessage;
            $markers['###ERROR_' . strtoupper($field) . '###'] = $errorMessage;
            $errorMessage = $clearErrorMessage;
            if ($this->settings['addErrorAnchors']) {
                $baseUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
                if ($this->globals->isAjaxMode()) {
                    $baseUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER');
                }
                $errorMessage = '<a href="' . $baseUrl . '#' . $field . '-' . $this->globals->getRandomID() . '">' . $errorMessage . '</a>';
            }

            //list settings
            $errorMessage = $this->utilityFuncs->wrap($errorMessage, $this->settings['errorListTemplate.'], 'singleWrap');
            $markers['###ERROR###'] .= $errorMessage;
        }
        $markers['###ERROR###'] = $this->utilityFuncs->wrap($markers['###ERROR###'], $this->settings['errorListTemplate.'], 'totalWrap');
        $langMarkers = $this->utilityFuncs->getFilledLangMarkers($markers['###ERROR###'], $this->langFiles);
        $markers['###ERROR###'] = $this->markerBasedTemplateService->substituteMarkerArray($markers['###ERROR###'], $langMarkers);
        $markers['###error###'] = $markers['###ERROR###'];
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    /**
     * Substitutes markers defined in TypoScript in $this->template
     *
     * @return void
     */
    protected function fillTypoScriptMarkers()
    {
        $markers = [];
        if (is_array($this->settings['markers.'])) {
            foreach ($this->settings['markers.'] as $name => $options) {
                if (!strstr($name, '.') && strstr($this->template, '###' . $name . '###')) {
                    $markers['###' . $name . '###'] = $this->utilityFuncs->getSingle($this->settings['markers.'], $name);
                }
            }
        }
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);
    }

    /**
     * Substitutes markers
     *        ###value_[fieldname]###
     *        ###VALUE_[FIELDNAME]###
     *        ###[fieldname]###
     *        ###[FIELDNAME]###
     * in $this->template
     *
     * @return void
     */
    protected function fillValueMarkers()
    {
        $this->disableEncodingFields = [];
        if ($this->settings['disableEncodingFields']) {
            $this->disableEncodingFields = explode(',', $this->utilityFuncs->getSingle($this->settings, 'disableEncodingFields'));
        }
        $markers = $this->getValueMarkers($this->gp);
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $markers);

        //remove remaining VALUE_-markers
        //needed for nested markers like ###LLL:tx_myextension_table.field1.i.###value_field1###### to avoid wrong marker removal if field1 isn't set
        $this->template = preg_replace('/###value_.*?###/i', '', $this->template);
    }

    protected function getValueMarkers($values, $level = 0, $prefix = 'value_', $doEncode = true)
    {
        $markers = [];

        $arrayValueSeparator = $this->utilityFuncs->getSingle($this->settings, 'arrayValueSeparator');
        if (strlen($arrayValueSeparator) === 0) {
            $arrayValueSeparator = ',';
        }
        if (is_array($values)) {
            foreach ($values as $k => $v) {
                $currPrefix = $prefix;
                if ($level === 0) {
                    $currPrefix .= $k;
                } else {
                    $currPrefix .= '|' . $k;
                }
                if (is_array($v)) {
                    $level++;
                    $markers = array_merge($markers, $this->getValueMarkers($v, $level, $currPrefix));
                    if ($doEncode) {
                        $v = $this->utilityFuncs->recursiveHtmlSpecialChars($v);
                    }
                    $v = implode($arrayValueSeparator, $v);
                    $level--;
                } elseif ($doEncode) {
                    if (!in_array($k, $this->disableEncodingFields)) {
                        $v = htmlspecialchars($v);
                    }
                }
                $v = trim($v);
                $markers['###' . $currPrefix . '###'] = $v;
                $markers['###' . strtoupper($currPrefix) . '###'] = $markers['###' . $currPrefix . '###'];
            }
        }
        return $markers;
    }

    protected function getSelectedMarkers($values, $level = 0, $prefix = 'selected_')
    {
        $markers = [];
        $activeString = 'selected="selected"';
        if (substr($prefix, 0, 8) === 'checked_') {
            $activeString = 'checked="checked"';
        }
        if (is_array($values)) {
            foreach ($values as $k => $v) {
                $currPrefix = $prefix;
                if ($level === 0) {
                    $currPrefix .= $k;
                } else {
                    $currPrefix .= '|' . $k;
                }
                if (is_array($v)) {
                    $level++;
                    $markers = array_merge($markers, $this->getSelectedMarkers($v, $level, $currPrefix));
                    foreach ($v as $arrayValue) {
                        $arrayValue = $this->utilityFuncs->recursiveHtmlSpecialChars($arrayValue);
                        $markers['###' . $currPrefix . '_' . $arrayValue . '###'] = $activeString;
                        $markers['###' . strtoupper($currPrefix) . '###'] = $markers['###' . $currPrefix . '_' . $arrayValue . '###'];
                    }
                    $level--;
                } else {
                    $v = htmlspecialchars($v);
                    $markers['###' . $currPrefix . '_' . $v . '###'] = $activeString;
                    $markers['###' . strtoupper($currPrefix) . '###'] = $markers['###' . $currPrefix . '_' . $v . '###'];
                }
            }
        }
        return $markers;
    }

    /**
     * Substitutes markers
     *        ###LLL:[languageKey]###
     * in $this->template
     *
     * @return void
     */
    protected function fillLangMarkers()
    {
        $langMarkers = [];
        if (is_array($this->langFiles)) {
            $aLLMarkerList = [];
            preg_match_all('/###LLL:[^#]+?###/Ssm', $this->template, $aLLMarkerList);
            foreach ($aLLMarkerList[0] as $idx => $LLMarker) {
                $llKey = substr($LLMarker, 7, (strlen($LLMarker) - 10));
                $marker = $llKey;
                $message = '';
                foreach ($this->langFiles as $subIdx => $langFile) {
                    $temp = trim($GLOBALS['TSFE']->sL('LLL:' . $langFile . ':' . $llKey));
                    if (strlen($temp) > 0) {
                        $message = $temp;
                    }
                }
                $langMarkers['###LLL:' . $marker . '###'] = $message;
            }
        }
        $this->template = $this->markerBasedTemplateService->substituteMarkerArray($this->template, $langMarkers);
    }

    /**
     * improved copy from dam_index
     *
     * Returns HTML of a box with a step counter and "back" and "next" buttons
     * Use label "next"/"prev" or "next_[stepnumber]"/"prev_[stepnumber]" for specific step in language file as button text.
     *
     * <code>
     * #set background color
     * plugin.Tx_Formhandler.settings.stepbar_color = #EAEAEA
     * #use default CSS, written to temp file
     * plugin.Tx_Formhandler.settings.useDefaultStepBarStyles = 1
     * </code>
     *
     * @author Johannes Feustel
     * @param    integer $currentStep current step (begins with 1)
     * @param    integer $lastStep last step
     * @param    string $buttonNameBack name attribute of the back button
     * @param    string $buttonNameFwd name attribute of the forward button
     * @return    string    HTML code
     */
    protected function createStepBar($currentStep, $lastStep, $buttonNameBack = '', $buttonNameFwd = '')
    {

        //colors
        $bgcolor = '#EAEAEA';
        $bgcolor = $this->settings['stepbar_color'] ? $this->settings['stepbar_color'] : $bgcolor;

        $nrcolor = \Typoheads\Formhandler\Utility\GeneralUtility::modifyHTMLcolor($bgcolor, 30, 30, 30);
        $errorbgcolor = '#dd7777';
        $errornrcolor = \Typoheads\Formhandler\Utility\GeneralUtility::modifyHTMLcolor($errorbgcolor, 30, 30, 30);

        $classprefix = $this->globals->getFormValuesPrefix() . '_stepbar';

        $css = [];
        $css[] = '.' . $classprefix . ' { background:' . $bgcolor . '; padding:4px;}';
        $css[] = '.' . $classprefix . '_error { background: ' . $errorbgcolor . ';}';
        $css[] = '.' . $classprefix . '_steps { margin-left:50px; margin-right:25px; vertical-align:middle; font-family:Verdana,Arial,Helvetica; font-size:22px; font-weight:bold; }';
        $css[] = '.' . $classprefix . '_steps span { color:' . $nrcolor . '; margin-left:5px; margin-right:5px; }';
        $css[] = '.' . $classprefix . '_error .' . $classprefix . '_steps span { color:' . $errornrcolor . '; margin-left:5px; margin-right:5px; }';
        $css[] = '.' . $classprefix . '_steps .' . $classprefix . '_currentstep { color:  #000;}';
        $css[] = '#stepsFormButtons { margin-left:25px;vertical-align:middle;}';

        $content = '';
        $buttons = '';

        for ($i = 1; $i <= $lastStep; $i++) {
            $class = '';
            if ($i == $currentStep) {
                $class = 'class="' . $classprefix . '_currentstep"';
            }
            $stepName = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'step-' . $i);
            if (strlen($stepName) === 0) {
                $stepName = $i;
            }
            $content .= '<span ' . $class . ' >' . $stepName . '</span>';
        }
        $content = '<span class="' . $classprefix . '_steps' . '">' . $content . '</span>';

        //if not the first step, show back button
        if ($currentStep > 1) {
            //check if label for specific step
            $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'prev_' . $currentStep);
            if (strlen($message) === 0) {
                $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'prev');
            }
            $buttons .= '<input type="submit" name="' . $buttonNameBack . '" value="' . trim($message) . '" class="button_prev" style="margin-right:10px;" />';
        }
        $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'next_' . $currentStep);
        if (strlen($message) === 0) {
            $message = $this->utilityFuncs->getTranslatedMessage($this->langFiles, 'next');
        }
        $buttons .= '<input type="submit" name="' . $buttonNameFwd . '" value="' . trim($message) . '" class="button_next" />';

        $content .= '<span id="stepsFormButtons">' . $buttons . '</span>';

        //wrap
        $classes = $classprefix;
        if ($this->errors) {
            $classes = $classes . ' ' . $classprefix . '_error';
        }
        $content = '<div class="' . $classes . '" >' . $content . '</div>';

        //add default css to page
        if ($this->settings['useDefaultStepBarStyles']) {
            $css = implode("\n", $css);
            $css = TSpagegen::inline2TempFile($css, 'css');
            if (version_compare(TYPO3_version, '4.3.0') >= 0) {
                $css = '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css) . '" />';
            }
            $GLOBALS['TSFE']->additionalHeaderData[$this->extKey . '_' . $classprefix] .= $css;
        }
        return $content;
    }
}
