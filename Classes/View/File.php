<?php
declare(strict_types=1);

namespace Typoheads\Formhandler\View;

class File extends Form
{
    public function render(array $gp, array $errors): string
    {
        $this->settings['disableWrapInBaseClass'] = 1;
        $content = parent::render($gp, []);
        return trim($content);
    }
}
