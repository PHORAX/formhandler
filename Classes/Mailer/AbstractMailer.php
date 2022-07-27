<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Mailer;

use Typoheads\Formhandler\Component\AbstractComponent;

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
 * An abstract mailer class. Mailers are used by Finisher_Mail.
 */
abstract class AbstractMailer extends AbstractComponent {
  /**
   * Not needed for this type of component.
   *
   * @return array<string, mixed>
   */
  public function process(mixed &$error = null): array {
    return [];
  }
}
