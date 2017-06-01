<?php

namespace Krutush\Form;

class Element{
    protected $data = array();

    public function __construct(string $name){
        $this->data['name'] = $name;
    }

    public function name() : string{ return $this->data['name']; }

    public function required(bool $value = true) : Element{
        $this->data['required'] = $value;
        return $this;
    }

    public function value(string $value) : Element{
        $this->data['value'] = $value;
        return $this;
    }

    public function get() : ?string{
        return $this->data['value'];
    }

    public function error(bool $value = true) : Element{
        $this->data['error'] = $value;
        return $this;
    }

    public function valid(mixed $data)/* :bool|string */{
        if((!isset($data) || empty($data)) && isset($this->data['required']) && $this->data['required'] == true)
            return 'requis';

        return true;
    }

    public function html(string $more = '') : string{
        return '<span '.$more.'></span>';
    }
}