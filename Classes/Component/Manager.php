<?php
namespace Typoheads\Formhandler\Component;

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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Component Manager originally written for the extension 'gimmefive'.
 * This is a backport of the Component Manager of FLOW3. It's based
 * on code mainly written by Robert Lemke. Thanx to the FLOW3 team for all the great stuff!
 *
 * Refactored for usage with Formhandler.
 */
class Manager implements SingletonInterface
{

    /**
     * The global Formhandler values
     *
     * @access protected
     * @var \Typoheads\Formhandler\Utility\Globals
     */
    protected $globals;

    /**
     * The global Formhandler values
     *
     * @access protected
     * @var \Typoheads\Formhandler\Utility\GeneralUtility
     */
    protected $utilityFuncs;

    public function __construct()
    {
        $this->globals = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\Globals::class);
        $this->utilityFuncs = GeneralUtility::makeInstance(\Typoheads\Formhandler\Utility\GeneralUtility::class);
    }

    /**
     * Returns a component object from the cache. If there is no object stored already, a new one is created and stored in the cache.
     *
     * @param string $componentName
     * @return mixed
     * @author Robert Lemke <robert@typo3.org>
     * @author adapted for TYPO3v4 by Jochen Rau <jochen.rau@typoplanet.de>
     */
    public function getComponent($componentName)
    {
        $componentName = $this->utilityFuncs->prepareClassName($componentName);
        //Avoid component manager creating multiple instances of itself
        if (get_class($this) === $componentName) {
            return $this;
        }
        $arguments = array_slice(func_get_args(), 1, null, true);

        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $componentObject = $objectManager->get($componentName, $arguments);

        return $componentObject;
    }
}
