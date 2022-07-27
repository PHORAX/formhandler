<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\AbstractAjax;
use Typoheads\Formhandler\Ajax\FormSubmit;
use Typoheads\Formhandler\Component\Manager;
use Typoheads\Formhandler\Session\AbstractSession;
use Typoheads\Formhandler\Utility\Globals;

class AjaxMiddleware implements MiddlewareInterface {
  protected RequestHandlerInterface $handler;

  /** @var array<string, array<string, string>> */
  protected array $pathParams = [];

  /** @var array<mixed> */
  protected array $queryParams = [];

  protected ServerRequestInterface $request;

  /** @var array<mixed>|object */
  protected array|object $requestBody = [];

  protected ResponseInterface $response;

  protected ResponseFactoryInterface $responseFactory;

  private Manager $componentManager;

  /**
   * The global Formhandler values.
   */
  private Globals $globals;

  /** @var array<string, mixed> */
  private array $settings = [];

  private \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs;

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $this->request = $request;
    $this->handler = $handler;

    $this->post('/formhandler/', \Closure::fromCallable([$this, 'validate']));
    $this->post('/formhandler/removefile/', \Closure::fromCallable([$this, 'removeFile']));
    $this->post('/formhandler/ajaxsubmit/', \Closure::fromCallable([$this, 'ajaxSubmit']));
    $this->post('/formhandler/submit/', \Closure::fromCallable([$this, 'submit']));

    return $this->handleRequests();
  }

  protected function addPathParam(string $path, string $key, string $val): void {
    $this->pathParams[$path] = array_merge_recursive($this->pathParams[$path] ?? [], [$key => $val]);
  }

  protected function checkRequest(string $path, callable $callable, string $method): void {
    if ($this->request->getMethod() == $method) {
      if ($this->isPath($path) || $this->isRequestTarget($path)) {
        $this->requestBody = $this->request->getParsedBody() ?? [];
        $this->queryParams = $this->request->getQueryParams();
        $this->response = $callable($this->queryParams, $this->pathParams[$path] ?? [], $this->requestBody);
      }
    }
  }

  protected function createResponse(string $string, bool $jsonOutput = true): ResponseInterface {
    $response = $this->responseFactory->createResponse();
    if ($jsonOutput) {
      $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    $response->getBody()->write($string);

    return $response;
  }

  protected function get(string $path, callable $callable): void {
    $this->checkRequest($path, $callable, 'GET');
  }

  protected function handleRequests(): ResponseInterface {
    if (isset($this->response)) {
      return $this->response;
    }

    return $this->handler->handle($this->request);
  }

  protected function isPath(string $expectedPath): bool {
    $path = $this->request->getUri()->getPath();

    if (0 == preg_match('/\{.*\}/', $expectedPath)) {
      return $path == $expectedPath ? true : false;
    }

    return $this->isPathWithVariables($expectedPath, $path);
  }

  protected function isPathWithVariables(string $expectedPath, string $path): bool {
    $pathFragments = preg_split('/\//', $path, 0, PREG_SPLIT_NO_EMPTY) ?: [];
    $expectedPathFragments = preg_split('/\//', $expectedPath, 0, PREG_SPLIT_NO_EMPTY) ?: [];

    $equal = true;
    if (sizeof($expectedPathFragments) == sizeof($pathFragments)) {
      for ($i = 0; $i < sizeof($expectedPathFragments); ++$i) {
        if (0 == preg_match('/\{([a-zA-Z0-9]*)\}/', $expectedPathFragments[$i])) {
          if ($expectedPathFragments[$i] != $pathFragments[$i]) {
            $equal = false;
          }
        } else {
          $this->addPathParam(
            $expectedPath,
            str_replace(['{', '}'], '', $expectedPathFragments[$i]),
            $pathFragments[$i]
          );
        }
      }
    } else {
      $equal = false;
    }

    return $equal;
  }

  protected function isRequestTarget(string $expectedPath): bool {
    $path = $this->request->getRequestTarget();

    if (0 == preg_match('/\{.*\}/', $expectedPath)) {
      return $path == $expectedPath ? true : false;
    }

    return $this->isPathWithVariables($expectedPath, $path);
  }

  protected function post(string $path, callable $callable): void {
    $this->checkRequest($path, $callable, 'POST');
  }

  /**
   * @param array<string, mixed> $queryParams
   * @param array<string, mixed> $pathParams
   * @param array<string, mixed> $requestBody
   */
  private function ajaxSubmit(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    $this->init();

    /** @var FormSubmit $form */
    $form = GeneralUtility::makeInstance(FormSubmit::class);
    $form->init($this->componentManager, $this->globals, $this->settings, $this->utilityFuncs);

    return $form->main();
  }

  /**
   * Initialize the class. Read GET parameters.
   */
  private function init(): void {
    $GLOBALS['TYPO3_REQUEST'] = $this->request;

    $id = (int) ($_GET['pid'] ?? $_GET['id'] ?? 0);

    $this->componentManager = GeneralUtility::makeInstance(Manager::class);

    /** @var \Typoheads\Formhandler\Utility\GeneralUtility $utilityFuncs */
    $utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
    $this->utilityFuncs = $utilityFuncs;
    $this->utilityFuncs->initializeTSFE($this->request);

    $elementUID = (int) $_GET['uid'];

    /** @var ConnectionPool $connectionPool */
    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');

    /** @var FrontendRestrictionContainer $frontendRestrictionContainer */
    $frontendRestrictionContainer = GeneralUtility::makeInstance(FrontendRestrictionContainer::class);
    $queryBuilder->setRestrictions($frontendRestrictionContainer);
    $row = $queryBuilder
      ->select('*')
      ->from('tt_content')
      ->where(
        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($elementUID, \PDO::PARAM_INT))
      )
      ->executeQuery()
      ->fetchAssociative()
    ;
    if (!empty($row)) {
      $GLOBALS['TSFE']->cObj->data = $row;
      $GLOBALS['TSFE']->cObj->current = 'tt_content_'.$elementUID;
    }

    /** @var Globals $globals */
    $globals = GeneralUtility::makeInstance(Globals::class);
    $this->globals = $globals;

    $this->globals->setCObj($GLOBALS['TSFE']->cObj);
    $randomID = htmlspecialchars(strval(GeneralUtility::_GP('randomID')));
    $this->globals->setRandomID($randomID);
    $this->globals->setAjaxMode(true);
    if (null == $this->globals->getSession()) {
      $ts = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_formhandler_pi1.']['settings.'] ?? [];
      $sessionClass = $this->utilityFuncs->getPreparedClassName(isset($ts['session.']) ? $ts['session.'] : [], 'Session\PHP');

      /** @var ?AbstractSession $sessionClassTemp */
      $sessionClassTemp = GeneralUtility::makeInstance($sessionClass);
      $this->globals->setSession($sessionClassTemp);
    }

    $this->settings = (array) ($this->globals->getSession()?->get('settings') ?? []);
    $this->globals->setLangFiles($this->utilityFuncs->readLanguageFiles([], $this->settings));
  }

  /**
   * @param array<string, mixed> $queryParams
   * @param array<string, mixed> $pathParams
   * @param array<string, mixed> $requestBody
   */
  private function removeFile(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    $this->init();

    // init ajax
    $className = [];
    if (isset($this->settings['ajax.']) && is_array($this->settings['ajax.']) && isset($this->settings['ajax.']['removeFile.']) && is_array($this->settings['ajax.']['removeFile.'])) {
      $className = $this->settings['ajax.']['removeFile.'];
    }

    $class = $this->utilityFuncs->getPreparedClassName($className, 'Ajax\RemoveFile');

    /** @var AbstractAjax $removeFile */
    $removeFile = GeneralUtility::makeInstance($class);
    $removeFile->init($this->componentManager, $this->globals, $this->settings, $this->utilityFuncs);

    return $removeFile->main();
  }

  /**
   * @param array<string, mixed> $queryParams
   * @param array<string, mixed> $pathParams
   * @param array<string, mixed> $requestBody
   */
  private function submit(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    $this->init();

    // init ajax
    $className = [];
    if (isset($this->settings['ajax.']) && is_array($this->settings['ajax.']) && isset($this->settings['ajax.']['submit.']) && is_array($this->settings['ajax.']['submit.'])) {
      $className = $this->settings['ajax.']['submit.'];
    }

    $class = $this->utilityFuncs->getPreparedClassName($className, 'Ajax\Submit');

    /** @var AbstractAjax $submit */
    $submit = GeneralUtility::makeInstance($class);
    $submit->init($this->componentManager, $this->globals, $this->settings, $this->utilityFuncs);

    return $submit->main();
  }

  /**
   * @param array<string, mixed> $queryParams
   * @param array<string, mixed> $pathParams
   * @param array<string, mixed> $requestBody
   */
  private function validate(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    $this->init();

    // init ajax
    $className = [];
    if (isset($this->settings['ajax.']) && is_array($this->settings['ajax.']) && isset($this->settings['ajax.']['validate.']) && is_array($this->settings['ajax.']['validate.'])) {
      $className = $this->settings['ajax.']['validate.'];
    }

    $class = $this->utilityFuncs->getPreparedClassName($className, 'Ajax\Validate');

    /** @var AbstractAjax $validate */
    $validate = GeneralUtility::makeInstance($class);
    $validate->init($this->componentManager, $this->globals, $this->settings, $this->utilityFuncs);

    return $validate->main();
  }
}
