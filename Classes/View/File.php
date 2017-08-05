<?php
namespace Typoheads\Formhandler\View;

class File extends Form
{
    public function render($gp, $errors)
    {
        $this->settings['disableWrapInBaseClass'] = 1;
        $content = parent::render($gp, array());
        return trim($content);
    }
}
