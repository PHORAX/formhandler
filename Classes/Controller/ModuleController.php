<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Domain\Model\Demand;
use Typoheads\Formhandler\Domain\Model\LogData;
use Typoheads\Formhandler\Domain\Repository\LogDataRepository;
use Typoheads\Formhandler\Generator\AbstractGenerator;
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
   *
   * @var array<string, mixed>
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

  private int $id = 0;

  /**
   * Exports given rows as file.
   *
   * @param ?string              $logDataUids uids to export
   * @param array<string, mixed> $fields      fields to export
   * @param string               $filetype    export file type (PDF || CSV)
   */
  public function exportAction(?string $logDataUids = null, array $fields = [], string $filetype = ''): void {
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

        /** @var AbstractGenerator $generator */
        $generator = GeneralUtility::makeInstance($className);
        $this->settings['pdf']['config']['records'] = $convertedLogDataRows;
        $this->settings['pdf']['config']['exportFields'] = $fields;
        $generator->init([], $this->settings['pdf']['config']);
        $generator->process();
      } elseif ('csv' === $filetype) {
        $className = $this->utilityFuncs->getPreparedClassName(
          $this->settings['csv'],
          BackendCsv::class
        );

        /** @var AbstractGenerator $generator */
        $generator = GeneralUtility::makeInstance($className);
        $this->settings['csv']['config']['records'] = $convertedLogDataRows;
        $this->settings['csv']['config']['exportFields'] = $fields;
        $generator->init([], $this->settings['csv']['config']);
        $generator->process();
      }
    }

    $this->addFlashMessage('The export field list must not be empty!', '', FlashMessage::ERROR);
    $this->redirect('index');
  }

  /**
   * Displays log data.
   */
  public function indexAction(Demand $demand = null, int $page = null): ResponseInterface {
    if (null === $demand) {
      /** @var Demand $demand */
      $demand = GeneralUtility::makeInstance(Demand::class);

      if (!isset($this->gp['demand']) || (isset($this->gp['demand']) && is_array($this->gp['demand']) && !isset($this->gp['demand']['pid']))) {
        $demand->setPid($this->id);
      }
    }
    if (null !== $page) {
      $demand->setPage($page);
    }
    $this->setStartAndEndTimeFromTimeSelector($demand);

    // TODO: findDemanded funktioniert nicht, da die Datepicker zunächst gefixt werden müssen
    $logDataRows = $this->logDataRepository->findDemanded($demand);
    $this->view->assign('demand', $demand);
    $this->view->assign('logDataRows', $logDataRows);
    $this->view->assign('settings', $this->settings);
    $this->view->assign('pagination', $this->preparePagination($demand));

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
    $this->id = intval($_GET['id'] ?? 0);

    $this->gp = $this->request->getArguments();
    $this->componentManager = GeneralUtility::makeInstance(Manager::class);
    $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);

    /** @var PageRenderer $pageRenderer */
    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    $this->pageRenderer = $pageRenderer;

    $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

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
        strval(PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED),
        true
      );
    }
    // or just allow certain properties
    // $propertyMappingConfiguration->allowProperties('firstname');
  }

  public function injectLogDataRepository(LogDataRepository $logDataRepository): void {
    $this->logDataRepository = $logDataRepository;
  }

  /**
   * Displays fields selector.
   *
   * @param ?string $logDataUids uids to export
   * @param string  $filetype    export file type (PDF || CSV)
   */
  public function selectFieldsAction(?string $logDataUids = null, string $filetype = ''): void {
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
      $data = [
        'crdate' => $logDataRow->getCrdate(),
        'ip' => $logDataRow->getIp(),
        'isSpam' => $logDataRow->getIsSpam(),
        'params' => unserialize($logDataRow->getParams()),
        'pid' => $logDataRow->getPid(),
        'uid' => $logDataRow->getUid(),
      ];

      $this->view->assign('data', $data);
      $this->view->assign('settings', $this->settings);
    }

    return $this->htmlResponse();
  }

  /**
   * Prepares information for the pagination of the module.
   *
   * @return array{count: int, current: int, numberOfPages: int, hasLessPages: bool, hasMorePages: bool, startRecord: int, endRecord: int, nextPage?: int, previousPage?: int}
   */
  protected function preparePagination(Demand $demand): array {
    $count = $this->logDataRepository->countRedirectsByByDemand($demand);
    $numberOfPages = (int) ceil($count / $demand->getLimit());
    $endRecord = $demand->getOffset() + $demand->getLimit();
    if ($endRecord > $count) {
      $endRecord = $count;
    }

    $pagination = [
      'count' => $count,
      'current' => $demand->getPage(),
      'numberOfPages' => $numberOfPages,
      'hasLessPages' => $demand->getPage() > 1,
      'hasMorePages' => $demand->getPage() < $numberOfPages,
      'startRecord' => $demand->getOffset() + 1,
      'endRecord' => $endRecord,
    ];
    if ($pagination['current'] < $pagination['numberOfPages']) {
      $pagination['nextPage'] = $pagination['current'] + 1;
    }
    if ($pagination['current'] > 1) {
      $pagination['previousPage'] = $pagination['current'] - 1;
    }

    return $pagination;
  }

  protected function setStartAndEndTimeFromTimeSelector(Demand $demand): void {
    $startTime = $demand->getManualDateStart() ? $demand->getManualDateStart()->getTimestamp() : 0;
    $endTime = $demand->getManualDateStop() ? $demand->getManualDateStop()->getTimestamp() : 0;
    $demand->setStartTimestamp($startTime);
    $demand->setEndTimestamp($endTime);
  }
}
