<?php

namespace Krutush\Template;

class Text extends Template{

    private $content = array();
    private $section;
    private $sections = array();

    public function content($key, $value){
        $this->content[$key] = $value;
        return $this;
    }

    public function contents(array $array){
        foreach($array as $key => $value){
            $this->content($key, $value);
        }
        return $this;
    }

    public function _content($key){
        if(isset($this->content[$key]))
            return $this->content[$key];

        return '';
    }

    public function extract(): array{
        $data = parent::extract();
        $data['contents'] = $this->sections;
        return $data;
    }

    public function insert(array $data): self{
        parent::setup();
        $this->contents($data['contents']);
        return $this;
    }

    public function _section(string $key){
        if(isset($this->section)){
            trigger_error('Section precedente non cloturée : '.$this->section, E_USER_WARM);
            return;
        }
        $this->section = $key;
        ob_start();
    }

    public function _endsection(bool $override = true): self{
        if(!isset($this->section))
            trigger_error('Aucune section en cours', E_USER_WARM);
        $this->sections[$this->section] = ($override == false ? $this->sections[$this->section] : '').ob_get_clean();
        $this->section = null;
        return $this;
    }

    public function _print(string $key, string $format = '{?}', array $filters = array('type' => 'string')): string{
        if(!$this->_exist($key))
            return '';

        return str_replace('{?}', $this->_get($key, $filters), $format);
    }
    public function _p(string $key, string $format = '{?}', array $filters = array('type' => 'string')){ return $this->_print($key, $format, $filters); }
}