<?php
namespace Typoheads\Formhandler\ViewHelpers\Widget;

/*                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class UriViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Widget\UriViewHelper
{

    /**
     * Get the URI for a non-AJAX Request.
     *
     * @param RenderingContextInterface $renderingContext
     * @param array $arguments
     * @return string the Widget URI
     */
    protected static function getWidgetUri(RenderingContextInterface $renderingContext, array $arguments)
    {
        $controllerContext = $renderingContext->getControllerContext();
        $uriBuilder = $controllerContext->getUriBuilder();
        $argumentPrefix = $controllerContext->getRequest()->getArgumentPrefix();
        $parameters = $arguments['arguments'] ?? [];
        if ($arguments['action'] ?? false) {
            $parameters['action'] = $arguments['action'];
        }
        if (($arguments['format'] ?? '') !== '') {
            $parameters['format'] = $arguments['format'];
        }
        $queryParameters = [$argumentPrefix => $parameters];

        // @todo: how to do this better
        $additionalParams = [
            'tx_formhandler_web_formhandlerlog' => [
                'demand' => $_POST['tx_formhandler_web_formhandlerlog']['demand'],
                'show' => $_POST['tx_formhandler_web_formhandlerlog']['show']
            ]
        ];
        $queryParameters = array_merge($queryParameters, $additionalParams);
        return $uriBuilder->reset()
            ->setArguments($queryParameters)
            ->setSection($arguments['section'])
            ->setUseCacheHash($arguments['useCacheHash'])
            ->setAddQueryString(true)
            ->setAddQueryStringMethod($arguments['addQueryStringMethod'])
            ->setArgumentsToBeExcludedFromQueryString([$argumentPrefix, 'cHash'])
            ->setFormat($arguments['format'])
            ->build();
    }
}
