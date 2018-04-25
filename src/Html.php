<?php

namespace Krutush\Template;

class Html extends Text{

    /** @var string */
    const EXTENTION = '.phtml';

    public static function _escape(string $data = null){
        return htmlspecialchars($data ?: '', ENT_QUOTES, 'UTF-8');
    }
    public static function _e(string $data = null){ return static::_escape($data); }

    public static function filter($data, string $key, string $value){
        $data = parent::filter($data, $key, $value);
        switch($key){
            case 'nl2br':
                if($value == true && is_string($data))
                    return nl2br($data);
                break;

            case '2br':
                if($value == true && is_string($data))
                    return str_replace("<br />\r\n <br />", '<br/>', nl2br($data));
                break;

            case 'escape':
                if($value == true && is_string($data))
                    return static::_escape($data);
                break;
        }
        return $data;
    }

    public function _print(string $key, string $format = '{?}', array $filters = array('type' => 'string', 'escape' => true)): string{
        if(!$this->_exist($key))
            return '';

        if(!isset($format))
            return $key;
            
        return str_replace('{?}', $this->_get($key, $filters), $format);
    }
    public function _p(string $key, string $format = '{?}', array $filters = array('type' => 'string', 'escape' => true)){ return $this->_print($key, $format, $filters); }
}
