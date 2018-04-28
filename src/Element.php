<?php

namespace Krutush\Form;

class Element{
    protected $data = array();

    public function __construct(string $name){
        $this->data['name'] = $name;
    }

    public function rename(string $name): self{
        $this->data['name'] = $name;
        return $this;
    } 

    public function name() : string{ return $this->data['name']; }

    public function id(string $id): self{
        $this->data['id'] = $name;
        return $this;
    }

    public function label(string $label, string $more = ''): self {
        $this->data['label'] = $label;
        $this->data['label.more'] = $more;
        return $this;
    }

    public function required(bool $value = true) : self{
        $this->data['required'] = $value;
        return $this;
    }

    public function value(string $value = null) : self{
        $this->data['value'] = $value;
        return $this;
    }

    public function get(){
        return $this->data['value'];
    }

    public function error(bool $value = true) : self{
        $this->data['error'] = $value;
        return $this;
    }

    public function valid($data)/* :bool|string */{
        if((!isset($data) || empty($data)) && isset($this->data['required']) && $this->data['required'] == true)
            return 'requis';

        return true;
    }

    protected function getId(): string{
        return isset($this->data['id']) ? $this->data['id'] : $this->data['name'];
    }

    protected function htmlLabel(): string{
        return isset($this->data['label']) ? '<label for="'.$this->getId().'" '.$this->data['label.more'].'>'.$this->data['label']."</label>\n" : '';
    }

    public function html(string $more = '') : string{
        return $this->htmlLabel().'<span '.$more.'></span>';
    }
}