<?php

declare(strict_types=1);

namespace Typoheads\Formhandler\TcaFormElement;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

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
class SubmittedValues extends AbstractFormElement {
  public function render(): array {
    $parameterArray = $this->data['parameterArray'];

    $fieldInformationResult = $this->renderFieldInformation();
    $fieldInformationHtml = $fieldInformationResult['html'];
    $resultArray = $this->mergeChildReturnIntoExistingResult($this->initializeResultArray(), $fieldInformationResult, false);

    $fieldId = StringUtility::getUniqueId('formengine-textarea-');

    $attributes = [
      'id' => $fieldId,
      'name' => htmlspecialchars($parameterArray['itemFormElName']),
      'size' => '30',
      'data-formengine-input-name' => htmlspecialchars($parameterArray['itemFormElName']),
    ];

    $classes = [
      'form-control',
      't3js-formengine-textarea',
      'formengine-textarea',
    ];
    $itemValue = $parameterArray['itemFormElValue'];
    $attributes['class'] = implode(' ', $classes);

    $html = [];
    $html[] = '<div class="formengine-field-item t3js-formengine-field-item" style="padding: 5px;">';
    $html[] = $fieldInformationHtml;
    $html[] = '<div class="form-wizards-wrap">';
    $html[] = '<div class="form-wizards-element">';
    $html[] = '<div class="form-control-wrap">';
    $html[] = '<textarea readonly rows="15" '.GeneralUtility::implodeAttributes($attributes, true).' >';
    $html[] = htmlspecialchars($itemValue, ENT_QUOTES);
    $html[] = '</textarea>';
    $html[] = '</div>';
    $html[] = '</div>';
    $html[] = '</div>';
    $html[] = '</div>';
    $resultArray['html'] = implode(LF, $html);

    return $resultArray;
  }
}
