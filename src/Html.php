<?php

namespace Krutush\Template;

class Html extends Text{

    public function _escape(string $data){
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    public function _e(string $data){ return $this->_escape($data); }

    public static function filter($data, string $key, string $value){
        $data = parent::filter($data);
        if($key == 'escape' && $value == true && is_string($data))
            return $this->_escape($data);
        return $data;
    }

    public function _print(string $key, string $format = null, array $filters = array('type' => 'string', 'escape' => true)): string{
        if(!$this->_exist($key))
            return '';

        if(!isset($format))
            return $key;
            
        return str_replace('{?}', $this->_get($key, $filters), $format);
    }
    public function _p(string $key, string $format = null, array $filters = array('type' => 'string', 'escape' => true)){ return $this->_print($key, $format, $filters); }
}