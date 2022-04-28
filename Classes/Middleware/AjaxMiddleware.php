<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\RemoveFile;
use Typoheads\Formhandler\Ajax\Submit;
use Typoheads\Formhandler\Ajax\Validate;

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

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $this->request = $request;
    $this->handler = $handler;

    $this->post('/formhandler/', \Closure::fromCallable([$this, 'validate']));
    $this->post('/formhandler/removefile/', \Closure::fromCallable([$this, 'removeFile']));
    $this->post('/formhandler/ajaxsubmit/', \Closure::fromCallable([$this, 'submit']));

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
  private function removeFile(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    /** @var RemoveFile $removeFile */
    $removeFile = GeneralUtility::makeInstance(
      RemoveFile::class,
    );

    return $removeFile->main($this->request);
  }

  /**
   * @param array<string, mixed> $queryParams
   * @param array<string, mixed> $pathParams
   * @param array<string, mixed> $requestBody
   */
  private function submit(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    /** @var Submit $submit */
    $submit = GeneralUtility::makeInstance(
      Submit::class,
    );

    return $submit->main($this->request);
  }

  /**
   * @param array<string, mixed> $queryParams
   * @param array<string, mixed> $pathParams
   * @param array<string, mixed> $requestBody
   */
  private function validate(array $queryParams, array $pathParams, array $requestBody): ResponseInterface {
    /** @var Validate $validate */
    $validate = GeneralUtility::makeInstance(
      Validate::class,
    );

    return $validate->main($this->request);
  }
}
