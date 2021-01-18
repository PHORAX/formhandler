<?php
namespace Typoheads\Formhandler\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * UserFunc for rendering of log entry
 */
class TcaFormNodeUtility extends \TYPO3\CMS\Backend\Form\Element\AbstractFormElement
{
    public function render()
    {
	    $result = $this->initializeResultArray();
        $params = unserialize($this->data['databaseRow']['params']);

	    if (is_array($params))  {
		    foreach($params as $key => $value)  {
		        $result['html'] .= '<tr><td style="padding: 4px 10px; font-style: italic;">'. htmlspecialchars($key) .'</td><td style="padding: 4px 10px;">'. htmlspecialchars($value) .'</td>';
		    }
	    }
	    $result['html'] = '<table>' . $result['html'] . '</table>';
        return $result;
    }
}
