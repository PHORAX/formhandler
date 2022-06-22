<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Controller;

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

/**
 * Content to be parsed.
 */
class Content {
  /**
   * The actual content.
   */
  protected string $content = '';

  /**
   * The constructor settings the internal attribute "content".
   */
  public function __construct(mixed $content) {
    if (is_string($content)) {
      $this->setContent($content);
    }
  }

  /**
   * Returns the internal attribute "content".
   *
   * @return string The content
   */
  public function getContent(): string {
    return $this->content;
  }

  /**
   * Sets the internal attribute "content".
   */
  public function setContent(string $content): void {
    $this->content = $content;
  }

  /**
   * Actually only returns the internal attribute "content".
   *
   * @return string The content
   */
  public function toString(): string {
    return $this->content;
  }
}
