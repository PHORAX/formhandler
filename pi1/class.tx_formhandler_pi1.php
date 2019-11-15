<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Dev-Team Typoheads <dev@typoheads.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Plugin 'Formhandler' for the 'formhandler' extension.
 */
class tx_formhandler_pi1 extends TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $prefixId = 'tx_formhandler_pi1';
    public $scriptRelPath = 'pi1/class.tx_formhandler_pi1.php';
    public $extKey = 'formhandler';

    /**
     * The main method of the PlugIn
     *
     * @param    string $content : The PlugIn content
     * @param    array $conf : The PlugIn configuration
     * @return    The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $dispatcher = new \Typoheads\Formhandler\Controller\Dispatcher();
        $dispatcher->cObj = &$this->cObj;
        return $dispatcher->main($content, $conf);
    }
}
