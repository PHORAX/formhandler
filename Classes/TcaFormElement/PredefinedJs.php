<?php
declare(strict_types = 1);
namespace Typoheads\Formhandler\TcaFormElement;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class PredefinedJs extends AbstractFormElement
{
    public function render()
    {
        $uid = null;
        $divId = $this->data['tabAndInlineStack'][0][1];
        $newRecord = ($this->data['command']=='new');

        if ($this->data['vanillaUid'] >0) {
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