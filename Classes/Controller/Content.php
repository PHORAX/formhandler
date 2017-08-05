<?php
namespace Typoheads\Formhandler\Controller;

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

/**
 * Content to be parsed.
 */
class Content
{

    /**
     * The actual content
     *
     * @access protected
     * @var string
     */
    protected $content;

    /**
     * The constructor settings the internal attribute "content"
     *
     * @return void
     */
    public function __construct($content)
    {
        $this->setContent($content);
    }

    /**
     * Sets the internal attribute "content"
     *
     * @param string $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Returns the internal attribute "content"
     *
     * @return string The content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Actually only returns the internal attribute "content"
     *
     * @return string The content
     */
    public function toString()
    {
        return $this->content;
    }
}
