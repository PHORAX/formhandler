<?php
namespace Typoheads\Formhandler\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Domain\Model\Demand;
use Typoheads\Formhandler\Domain\Model\LogData;
use Typoheads\Formhandler\Utility\GeneralUtility as FormhandlerGeneralUtility;

class ModuleController extends ActionController
{

    /**
     * The request arguments
     *
     * @access protected
     * @var array
     */
    protected $gp;

    /**
     * The Formhandler component manager
     *
     * @access protected
     * @var Manager
     */
    protected $componentManager;

    /**
     * The Formhandler utility funcs
     *
     * @access protected
     * @var FormhandlerGeneralUtility
     */
    protected $utilityFuncs;

    /**
     * @var \Typoheads\Formhandler\Domain\Repository\LogDataRepository
     * @inject
     */
    protected $logDataRepository;

    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected $pageRenderer;

    /**
     * init all actions
     */
    public function initializeAction()
    {
        $this->id = (int)$_GET['id'];

        $this->gp = $this->request->getArguments();
        $this->componentManager = GeneralUtility::makeInstance(Manager::class);
        $this->utilityFuncs = GeneralUtility::makeInstance(FormhandlerGeneralUtility::class);
        $this->pageRenderer = $this->objectManager->get('TYPO3\CMS\Core\Page\PageRenderer');

        if (!isset($this->settings['dateFormat'])) {
            $this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 'm-d-Y' : 'd-m-Y';
        }
        if (!isset($this->settings['timeFormat'])) {
            $this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        }

        if ($this->arguments->hasArgument('demand')) {
            $propertyMappingConfiguration = $this->arguments['demand']->getPropertyMappingConfiguration();
            // allow all properties:
            $propertyMappingConfiguration->allowAllProperties();
            $propertyMappingConfiguration->setTypeConverterOption(
                'TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter',
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                true
            );
        }
        // or just allow certain properties
        //$propertyMappingConfiguration->allowProperties('firstname');
    }

    /**
     * Displays log data
     * @param Demand|null $demand
     */
    public function indexAction(Demand $demand = null)
    {
        if ($demand === null) {
            $demand = $this->objectManager->get('Typoheads\Formhandler\Domain\Model\Demand');
            if (!isset($this->gp['demand']['pid'])) {
                $demand->setPid($this->id);
            }
        }

        $logDataRows = $this->logDataRepository->findDemanded($demand);
        $this->view->assign('demand', $demand);
        $this->view->assign('logDataRows', $logDataRows);
        $this->view->assign('settings', $this->settings);
        if (!$this->gp['show']) {
            $this->gp['show'] = 10;
        }
        $this->view->assign('showItems', $this->gp['show']);
        $permissions = [];
        if ($GLOBALS['BE_USER']->user['admin'] || (int)$this->settings['enableClearLogs'] === 1) {
            $permissions['delete'] = true;
        }
        $this->view->assign('permissions', $permissions);
    }

    /**
     * @param LogData|NULL $logDataRow
     */
    public function viewAction(LogData $logDataRow = null)
    {
        if ($logDataRow !== null) {
            $logDataRow->setParams(unserialize($logDataRow->getParams()));
            $this->view->assign('data', $logDataRow);
            $this->view->assign('settings', $this->settings);
        }
    }

    /**
     * Displays fields selector
     * @param string uids to export
     * @param string export file type (PDF || CSV)
     */
    public function selectFieldsAction($logDataUids = null, $filetype = '')
    {
        if ($logDataUids !== null) {
            if ($this->settings[$filetype]['config']['fields']) {
                $fields = GeneralUtility::trimExplode(',', $this->settings[$filetype]['config']['fields']);
                $this->redirect(
                    'export',
                    null,
                    null,
                    [
                        'logDataUids' => $logDataUids,
                        'fields' => $fields,
                        'filetype' => $filetype
                    ]
                );
            }

            $logDataRows = $this->logDataRepository->findByUids($logDataUids);
            $fields = [
                'global' => [
                    'pid',
                    'ip',
                    'submission_date'
                ],
                'system' => [
                    'randomID',
                    'removeFile',
                    'removeFileField',
                    'submitField',
                    'submitted'
                ],
                'custom' => []
            ];
            foreach ($logDataRows as $logDataRow) {
                $params = unserialize($logDataRow->getParams());
                if (is_array($params)) {
                    $rowFields = array_keys($params);
                    foreach ($rowFields as $idx => $rowField) {
                        if (in_array($rowField, $fields['system'])) {
                            unset($rowFields[$idx]);
                        } elseif (substr($rowField, 0, 5) === 'step-') {
                            unset($rowFields[$idx]);
                            if (!in_array($rowField, $fields['system'])) {
                                $fields['system'][] = $rowField;
                            }
                        } elseif (!in_array($rowField, $fields['custom'])) {
                            $fields['custom'][] = $rowField;
                        }
                    }
                }
            }
            $this->view->assign('fields', $fields);
            $this->view->assign('logDataUids', $logDataUids);
            $this->view->assign('filetype', $filetype);
            $this->view->assign('settings', $this->settings);
        }
    }

    /**
     * Exports given rows as file
     * @param string uids to export
     * @param array fields to export
     * @param string export file type (PDF || CSV)
     */
    public function exportAction($logDataUids = null, array $fields, $filetype = '')
    {
        if ($logDataUids !== null && !empty($fields)) {
            $logDataRows = $this->logDataRepository->findByUids($logDataUids);
            $convertedLogDataRows = [];
            foreach ($logDataRows as $idx => $logDataRow) {
                $convertedLogDataRows[] = [
                    'pid' => $logDataRow->getPid(),
                    'ip' => $logDataRow->getIp(),
                    'crdate' => $logDataRow->getCrdate(),
                    'params' => unserialize($logDataRow->getParams())
                ];
            }
            if ($filetype === 'pdf') {
                $className = $this->utilityFuncs->getPreparedClassName(
                    $this->settings['pdf'],
                    '\Typoheads\Formhandler\Generator\BackendTcPdf'
                );

                $generator = $this->componentManager->getComponent($className);
                $this->settings['pdf']['config']['records'] = $convertedLogDataRows;
                $this->settings['pdf']['config']['exportFields'] = $fields;
                $generator->init([], $this->settings['pdf']['config']);
                $generator->process();
            } elseif ($filetype === 'csv') {
                $className = $this->utilityFuncs->getPreparedClassName(
                    $this->settings['csv'],
                    '\Typoheads\Formhandler\Generator\BackendCsv'
                );

                $generator = $this->componentManager->getComponent($className);
                $this->settings['csv']['config']['records'] = $convertedLogDataRows;
                $this->settings['csv']['config']['exportFields'] = $fields;
                $generator->init([], $this->settings['csv']['config']);
                $generator->process();
            }
        }
    }

    /**
     * Deletes given logs or all if value is "all"
     * @param string uids to delete
     */
    public function deleteLogRowsAction($logDataUids = null)
    {
        $forceDelete = (int)$this->settings['forceDelete'];
        if ($logDataUids === 'all') {
            $text = LocalizationUtility::translate('message.deleted-all-logs', 'formhandler');
            if ($forceDelete) {
                $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_formhandler_log');
            } else {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_formhandler_log', '1=1', ['deleted' => 1]);
            }
        } else {
            $logDataUids = explode(',', $logDataUids);
            $text = sprintf(LocalizationUtility::translate('message.deleted-log-rows', 'formhandler'), count($logDataUids));
            if ($forceDelete) {
                $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_formhandler_log', 'uid IN (' . implode(',', $logDataUids) . ')');
            } else {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_formhandler_log', 'uid IN (' . implode(',', $logDataUids) . ')', ['deleted' => 1]);
            }
        }

        $this->addFlashMessage($text);
        $this->redirect('index');
    }
}
