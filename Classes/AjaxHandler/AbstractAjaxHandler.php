<?php
namespace Typoheads\Formhandler\AjaxHandler;

/*                                                                       *
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

/**
 * Abstract class for an AjaxHandler.
 * The AjaxHandler takes care of adding AJAX related markers and JS used for validation and file removal.
 * @abstract
 */
abstract class AbstractAjaxHandler extends \Typoheads\Formhandler\Component\AbstractClass
{

    /**
     * Initialize AJAX stuff
     *
     * @return void
     */
    abstract public function initAjax();

    /**
     * Initialize the AjaxHandler
     *
     * @param array $settings The settings of the AjaxHandler
     * @return void
     */
    public function init($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Method called by the view to let the AjaxHandler add its markers.
     *
     * The view passes the marker array by reference.
     *
     * @param array &$markers Reference to the marker array
     * @return void
     */
    abstract public function fillAjaxMarkers(&$markers);

    /**
     * Method called by the view to get an AJAX based file removal link.
     *
     * @param string $text The link text to be used
     * @param string $field The field name of the form field
     * @param string $uploadedFileName The name of the file to be deleted
     * @return void
     */
    abstract public function getFileRemovalLink($text, $field, $uploadedFileName);
}
