<?php

namespace Simplon\Form\Elements\Hidden;

use Simplon\Form\Elements\CoreElement;

class HiddenElement extends CoreElement
{
    protected $elementHtml = '<input type="hidden" name=":id" value=":value">';

    /**
     * @return array
     */
    public function render()
    {
        return [
            'element' => $this->parseFieldPlaceholders($this->getElementHtml()),
        ];
    }
}