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

class UriViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Widget\UriViewHelper
{
    protected function getWidgetUri()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();

        $arguments = $this->hasArgument('arguments') ? $this->arguments['arguments'] : [];
        if ($this->hasArgument('action')) {
            $arguments['action'] = $this->arguments['action'];
        }
        if ($this->hasArgument('format') && $this->arguments['format'] !== '') {
            $arguments['format'] = $this->arguments['format'];
        }
        if ($this->hasArgument('addQueryStringMethod') && $this->arguments['addQueryStringMethod'] !== '') {
            $arguments['addQueryStringMethod'] = $this->arguments['addQueryStringMethod'];
        }

        $queryParameters = [$argumentPrefix => $arguments];

        //@TODO: HOW TO DO THIS BETTER?
        $additionalParams = [
            'tx_formhandler_web_formhandlerlog' => [
                'demand' => $_POST['tx_formhandler_web_formhandlerlog']['demand'],
                'show' => $_POST['tx_formhandler_web_formhandlerlog']['show']
            ]
        ];
        $queryParameters = array_merge($queryParameters, $additionalParams);
        return $uriBuilder->reset()->setArguments($queryParameters)->setSection($this->arguments['section'])->setAddQueryString(true)->setAddQueryStringMethod($this->arguments['addQueryStringMethod'])->setArgumentsToBeExcludedFromQueryString([$argumentPrefix, 'cHash'])->setFormat($this->arguments['format'])->build();
    }
}
