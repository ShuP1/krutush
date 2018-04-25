<?php

namespace Krutush\Form;

class TextArea extends Element{
    public function valid($data)/*: bool|string*/{
        return parent::valid($data);
    }

    public function html(string $more = '') : string{
        return '<textarea name="'.$this->data['name'].'" '.
        (isset($this->data['value']) ? 'value="'.$this->data['value'].'" ' : '').
        (isset($this->data['required']) && $this->data['required'] == true ? 'required ' : '').
        $more.'></textarea>';
    }
}