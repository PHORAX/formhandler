<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\ViewHelpers\Widget;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
 class UriViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\UriViewHelper {
   /**
    * Get the URI for a non-AJAX Request.
    *
    * @return string the Widget URI
    */
   protected static function getWidgetUri(RenderingContextInterface $renderingContext, array $arguments): string {
     // TODO: Fix me

     //  $controllerContext = $renderingContext->getControllerContext();
     //  $uriBuilder = $controllerContext->getUriBuilder();
     //  $argumentPrefix = $controllerContext->getRequest()->getArgumentPrefix();
     //  $parameters = $arguments['arguments'] ?? [];
     //  if ($arguments['action'] ?? false) {
     //    $parameters['action'] = $arguments['action'];
     //  }
     //  if (($arguments['format'] ?? '') !== '') {
     //    $parameters['format'] = $arguments['format'];
     //  }
     //  $queryParameters = [$argumentPrefix => $parameters];

     //  // @todo: how to do this better
     //  $additionalParams = [
     //    'tx_formhandler_web_formhandlerlog' => [
     //      'demand' => $_POST['tx_formhandler_web_formhandlerlog']['demand'],
     //      'show' => $_POST['tx_formhandler_web_formhandlerlog']['show'],
     //    ],
     //  ];
     //  $queryParameters = array_merge($queryParameters, $additionalParams);

     //  return $uriBuilder->reset()
     //    ->setArguments($queryParameters)
     //    ->setSection($arguments['section'])
     //    ->setUseCacheHash($arguments['useCacheHash'])
     //    ->setAddQueryString(true)
     //    ->setAddQueryStringMethod($arguments['addQueryStringMethod'])
     //    ->setArgumentsToBeExcludedFromQueryString([$argumentPrefix, 'cHash'])
     //    ->setFormat($arguments['format'])
     //    ->build()
     //  ;
     return '';
   }
 }
