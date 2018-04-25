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

    public function html(string $more = '') : string{
        return '<span '.$more.'></span>';
    }
}