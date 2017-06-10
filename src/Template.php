<?php

namespace Krutush\Template;

class Template{
    private $path;
    private $layout;
    private $data = array();
    private $content = array();
    private $section;
    private $sections = array();

    public function __construct($path, $extention = true, $folder = true){
        $this->path = $this->path($path, $extention, $folder);
    }

    public function set($key, $value){
        $this->data[$key] = $value;
        return $this;
    }

    public function sets(array $array){
        foreach($array as $key => $value){
            $this->set($key, $value);
        }
        return $this;
    }

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

    public function run($output = 'direct'){
        switch($output){
            case 'array':
            case 'direct':
                break;

            case 'buffer':
                ob_start();
                break;

            default:
                trigger_error('Unknow output type '.$output);
                break;
        }
        $callable = function($t, $path){
            include($path);
        };
        $callable($this, $this->path);
        if(isset($this->layout)){
            $layout = new Template($this->layout, false, false);
            $layout->sets($this->data)
                ->contents($this->sections)
                ->run();
        }
        switch($output){
            case 'direct':
                break;

            case 'buffer':
                return ob_get_clean();

            case 'array':
                return array(
                    'sets' => $this->data,
                    'contents' => $this->sections
                );

            default:
                break;
        }
    }

    public function _load($path, $extention = true, $folder = true){
        $load = new Template($path, $extention, $folder);
        $load->sets($this->data)
            ->contents($this->sections)
            ->run();
        $this->sets($load->data)
            ->contents($load->sections);
        return $this;
    }

    public function _layout($path, $extention = true, $folder = true){
        $this->layout = $this->path($path, $extention, $folder);
        return $this;
    }

    public function path($path, $extention = true, $folder = true){
        switch($extention){
            case true:
                $path .= '.phtml';
                break;

            case false:
                break;

            default:
                $path .= $extention;
                break;
        }
        if($folder == true)
            $path = Path::get('template').'/'.$path;

        return $path;
    }

    public function _content($key){
        if(isset($this->content[$key]))
            return $this->content[$key];

        return '';
    }

    public function _section($key){
        if(isset($this->section)){
            trigger_error('Section precedente non cloturée : '.$this->section, E_USER_WARM);
            return;
        }
        $this->section = $key;
        ob_start();
    }

    public function _endsection($override = true){
        if(!isset($this->section)){
            trigger_error('Aucune section en cours', E_USER_WARM);
            return;
        }
        $this->sections[$this->section] = ($override == false ? $this->sections[$this->section] : '').ob_get_clean();
        $this->section = null;
        return $this;
    }

    public function _escape($data){
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    public function _e($data){ return $this->_escape($data); }

    public function _exists($keys, $all = true, $exception = false){
        if(is_array($keys)){
            foreach($keys as $key){
                if(isset($this->data[$key])){
                    if(!$all)
                        return true;
                }else{
                    if($all)
                        return false;
                }
            }
            return $all;
        }else{
            if(is_string($keys)){
                return isset($this->data[$keys]);
            }else{
                if($exception)
                    throw new \Exception('key must be a string');
                return false;
            }
        }
    }

    public function _x($keys, $all = true){ return $this->_exists($keys, $all); }

    public function _get($key, $escape = true){
        if(!$this->_exists($key))
            return null;
        
        $value = $this->data[$key];
        if($escape && is_string($value))
            $value = $this->_escape($value);

        return $value;
    }
    public function _($key, $escape = true){ return $this->_get($key, $escape); }

    public function _print($key, $format, $escape = true){
        if(!$this->_exists($key))
            return '';

        return str_replace('{?}', $this->_get($key, $escape), $format);
    }
    public function _p($key, $format, $escape = true){ return $this->_print($key, $format, $escape); }
}