<?php

namespace Krutush\Form;

use Krutush\Template\Html;

class Form {
    private $method;
    private $url;
    private $elements = array();
    private $name;
    private $errors = array();
    private $set = false;

    public function __construct(string $name, string $path, string $extention = null, bool $folder = true, array $sets = array()){
        $this->name = $name;
        $tpl = new Html($path, $extention, $folder);
        $tpl->set($name, $this)
            ->sets($sets)
            ->run('buffer');
        return $this;
    }

    public static function sanitize(array $data) : array{
        $return = array();
        foreach($data as $key => $value){
            if(is_string($value))
                $return[$key] = strip_tags(trim($value));
        }
        return $return;
    }

    public function valid(array $data) : bool{
        $data = static::sanitize($data);
        $this->set = true;
        $valid = true;
        foreach($this->elements as $element){
            $value = isset($data[$element->name()]) ? $data[$element->name()] : null;
            $return = $element->valid($value);
            if($return !== true){
                $this->error('Le champ '.$element->name().' est '.$return.'.');
                $valid = false;
            }else{
                $element->value($value);
            }
        }
        return $valid;
    }

    public function error(string $error){
        $this->errors[] = $error;
    }

    public function name() : string{
        return $this->name;
    }

    public function _start(string $more = '', string $method = 'post', string $url = null) : string{
        if(!in_array($method, array('post', 'get')))
            $method = 'post';

        if($this->set == false){
            $this->method = $method;
            $this->url = $url;
        }
        $html = '<form method="'.$method.'" '.(isset($url) ? 'action="'.$url.'" ' : '').$more.'>';
        $html .= "
<script type=\"text/javascript\">
function SelectOther(source, other){
    if(source.tagName == 'SELECT' && source.value == other){
        source.style.display = 'none';
        source.disabled = true;
        var input = document.getElementsByName(source.name)[1];
        input.style.display = '';
        input.disabled = false;
        input.value = source.value;
    }else if(source.tagName == 'INPUT' && source.value.length == 0){
        source.style.display = 'none';
        source.disabled = true;
        var select = document.getElementsByName(source.name)[0];
        select.style.display = '';
        select.disabled = false;
        select.value = '';
    }
}
</script>
";
        return $html;
    }

    public function _end(string $more = '') : string{
        return '</form '.$more.'>';
    }

    public function _errors(string $more = '') : string{
        if(empty($this->errors))
            return '';

        $html = '<div class="errors" '.$more.'>';
        foreach($this->errors as $error){
            $html .= '<p>'.$error.'</p>';
        }
        return $html.'</div>';
    }

    public function _submit(string $name = null, string $more = '') : string{
        return '<input type="submit" '.(isset($name) ? 'value="'.$name.'" ' : '').$more.'>';
    }

    function _input(string $name, bool $add = true) : Element{
        if($add == false)
            return new Input($name);

        if($this->set == true){
            $input = $this->get($name);
            if(isset($input))
                return $input;
        }
        $input = new Input($name);
        $this->add($input);
        return $input;
    }

    function _select(string $name, bool $add = true) : Element{
        if($add == false)
            return new Select($name);
        if($this->set == true){
            $input = $this->get($name);
            if(isset($input))
                return $input;
        }
        $input = new Select($name);
        $this->add($input);
        return $input;
    }

    function _textarea(string $name) : Element{
        if($this->set == true){
            $input = $this->get($name);
            if(isset($input))
                return $input;
        }
        $input = new TextArea($name);
        $this->add($input);
        return $input;
    }

    public function add(Element $thing){
        if($this->set == false)
            $this->elements[] = $thing;
    }

    public function get(string $name) : Element{
        foreach($this->elements as $element){
            if($element->name() == $name)
                return $element;
        }
        return null;
    }

    public function values(bool $nullToEmpty = false) : array{
        $values = array();
        foreach($this->elements as $element){
            $value = $element->get();
            $values[$element->name()] = $nullToEmpty && !isset($value) ? '' : $value;
        }
        return $values;
    }
}