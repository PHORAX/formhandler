<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\Finisher;

/*
 *
 * This file is part of the "Formhandler" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2022 Manuel Gerber <gerber@jakota.de>, Jakota Design Group GmbH
 *
 */
class ResponseError {
  public string $failed = '';

  public string $fieldSelector = '';

  public string $message = '';
}
