<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Domain\Model\Demand;
use Typoheads\Formhandler\Domain\Model\LogData;
use Typoheads\Formhandler\Domain\Repository\LogDataRepository;
use Typoheads\Formhandler\Generator\BackendCsv;

/**
 * This script is part of the TYPO3 project - inspiring people to share!
 *
 * TYPO3 is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2 as published by
 * the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 */
class ModuleController extends ActionController {
  /**
   * The Formhandler component manager.
   */
  protected Manager $componentManager;

  /**
   * The request arguments.
   */
  protected array $gp;

  /**
   * @TYPO3\CMS\Extbase\Annotation\Inject
   */
  protected LogDataRepository $logDataRepository;

  protected PageRenderer $pageRenderer;

  /**
   * The Formhandler utility funcs.
   */
  protected \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs;

  /**
   * Exports given rows as file.
   *
   * @param string uids to export
   * @param array fields to export
   * @param string export file type (PDF || CSV)
   */
  public function exportAction(?string $logDataUids = null, array $fields = [], string $filetype = ''): ResponseInterface {
    if (null !== $logDataUids && !empty($fields)) {
      $logDataRows = $this->logDataRepository->findByUids($logDataUids);
      $convertedLogDataRows = [];
      foreach ($logDataRows as $idx => $logDataRow) {
        $convertedLogDataRows[] = [
          'pid' => $logDataRow->getPid(),
          'ip' => $logDataRow->getIp(),
          'crdate' => $logDataRow->getCrdate(),
          'params' => unserialize($logDataRow->getParams()),
        ];
      }
      if ('pdf' === $filetype) {
        $className = $this->utilityFuncs->getPreparedClassName(
          $this->settings['pdf'],
          '\Typoheads\Formhandler\Generator\BackendTcPdf'
        );

        $generator = $this->componentManager->getComponent($className);
        $this->settings['pdf']['config']['records'] = $convertedLogDataRows;
        $this->settings['pdf']['config']['exportFields'] = $fields;
        $generator->init([], $this->settings['pdf']['config']);
        $generator->process();
      } elseif ('csv' === $filetype) {
        $className = $this->utilityFuncs->getPreparedClassName(
          $this->settings['csv'],
          BackendCsv::class
        );

        $generator = $this->componentManager->getComponent($className);
        $this->settings['csv']['config']['records'] = $convertedLogDataRows;
        $this->settings['csv']['config']['exportFields'] = $fields;
        $generator->init([], $this->settings['csv']['config']);
        $generator->process();
      }
    }

    return $this->htmlResponse();
  }

  /**
   * Displays log data.
   */
  public function indexAction(Demand $demand = null): ResponseInterface {
    if (null === $demand) {
      $demand = $this->objectManager->get('Typoheads\Formhandler\Domain\Model\Demand');
      if (!isset($this->gp['demand']['pid'])) {
        $demand->setPid($this->id);
      }
    }

    // @TODO findDemanded funktioniert nicht, da die Datepicker zunächst gefixt werden müssen
    $logDataRows = $this->logDataRepository->findDemanded($demand);
    $this->view->assign('demand', $demand);
    $this->view->assign('logDataRows', $logDataRows);
    $this->view->assign('settings', $this->settings);
    if (!isset($this->gp['show'])) {
      $this->gp['show'] = 10;
    }
    $this->view->assign('showItems', $this->gp['show']);
    $permissions = [];
    $this->view->assign('permissions', $permissions);

    return $this->htmlResponse();
  }

  /**
   * init all actions.
   */
  public function initializeAction(): void {
    $this->id = (int) ($_GET['id'] ?? 0);

    $this->gp = $this->request->getArguments();
    $this->componentManager = GeneralUtility::makeInstance(Manager::class);
    $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
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
        // $propertyMappingConfiguration->allowProperties('firstname');
  }

  public function injectLogDataRepository(LogDataRepository $logDataRepository) {
    $this->logDataRepository = $logDataRepository;
  }

  /**
   * Displays fields selector.
   *
   * @param string uids to export
   * @param string export file type (PDF || CSV)
   */
  public function selectFieldsAction(?string $logDataUids = null, string $filetype = '') {
    if (null !== $logDataUids) {
      if (isset($this->settings[$filetype]['config']['fields'])) {
        $fields = GeneralUtility::trimExplode(',', $this->settings[$filetype]['config']['fields']);
        $this->redirect(
          'export',
          null,
          null,
          [
            'logDataUids' => $logDataUids,
            'fields' => $fields,
            'filetype' => $filetype,
          ]
        );
      }

      $logDataRows = $this->logDataRepository->findByUids($logDataUids);
      $fields = [
        'global' => [
          'pid',
          'ip',
          'submission_date',
        ],
        'system' => [
          'randomID',
          'removeFile',
          'removeFileField',
          'submitField',
          'submitted',
        ],
        'custom' => [],
      ];
      foreach ($logDataRows as $logDataRow) {
        $params = unserialize($logDataRow->getParams());
        if (is_array($params)) {
          $rowFields = array_keys($params);
          foreach ($rowFields as $idx => $rowField) {
            if (in_array($rowField, $fields['system'])) {
              unset($rowFields[$idx]);
            } elseif ('step-' === substr($rowField, 0, 5)) {
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

  public function viewAction(LogData $logDataRow = null): ResponseInterface {
    if (null !== $logDataRow) {
      $logDataRow->setParams(unserialize($logDataRow->getParams()));
      $this->view->assign('data', $logDataRow);
      $this->view->assign('settings', $this->settings);
    }

    return $this->htmlResponse();
  }
}
