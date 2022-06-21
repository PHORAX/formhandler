<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\TcaFormElement;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
class PredefinedJs extends AbstractFormElement {
  /**
   * @return array<string, mixed>
   */
  public function render(): array {
    $uid = null;
    $divId = $this->data['tabAndInlineStack'][0][1];
    $newRecord = ('new' == $this->data['command']);

    if ($this->data['vanillaUid'] > 0) {
      $uid = $this->data['vanillaUid'];
    }

    $js = "<script>\n";
    $js .= "/*<![CDATA[*/\n";

    $js .= "var uid = '".$uid."'\n";
    $js .= "var flexformBoxId = '".$divId."'\n";
    $js .= 'var newRecord = '.$newRecord."\n";
    $js .= file_get_contents(ExtensionManagementUtility::extPath('formhandler').'Resources/Public/JavaScript/addFields_predefinedJS.js');
    $js .= "/*]]>*/\n";
    $js .= "</script>\n";

    $result = $this->initializeResultArray();
    $result['html'] = $js;

    return $result;
  }
}
