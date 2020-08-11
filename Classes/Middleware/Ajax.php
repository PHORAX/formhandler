<?php
namespace Typoheads\Formhandler\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Typoheads\Formhandler\Ajax\RemoveFile;
use Typoheads\Formhandler\Ajax\Submit;
use Typoheads\Formhandler\Ajax\Validate;


/**
 * Handle Ajax calls (which were further by eID)
 */
class Ajax implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
    	
    	$GLOBALS['TSFE']->newCobj();
    	
    	$middlewareType = $request->getQueryParams()['formhandler_eID_middleware_ajaxmethod'];
	    
    	if (!$middlewareType)    {
    		return $handler->handle($request);
	    }

    	switch($middlewareType) {
		    case 'formhandler-validate':
		    	return GeneralUtility::makeInstance(Validate::class)->main($request, $handler);
		    case 'formhandler-removefile':
		    	return GeneralUtility::makeInstance(RemoveFile::class)->main($request, $handler);
		    case 'formhandler-ajaxsubmit':
		    	return GeneralUtility::makeInstance(Submit::class)->main($request, $handler);
		    default:
		    	$body = new Stream('php://temp', 'rw');
                $body->write('formhandler: ajax method not supported');
		    	return (new Response())
	                ->withHeader('content-type', 'text/plain; charset=utf-8')
	                ->withBody($body)
	                ->withStatus(404);
	    }

        //return $handler->handle($request);
    }
}
