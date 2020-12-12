<?php

namespace Krutush\Template;

class Template{
    /** @var string */
    private $path;
    /** @var string */
    private $layout;
    /** @var array */
    private $data = array();
    /** @var string */
    const EXTENTION = '.tpl';

    public function __construct(string $path, string $extention = null, bool $folder = true){
        $this->path = $this->path($path, $extention, $folder);
    }

    public function set(string $key, mixed $value): self{
        $this->data[$key] = $value;
        return $this;
    }

    public function sets(array $array){
        foreach($array as $key => $value){
            $this->set($key, $value);
        }
        return $this;
    }

    public function extract(): array{
        return [
            'sets' => $this->data
        ];
    }

    public function insert(array $data): self{
        $this->sets($data['sets']);
        return $this;
    }

    public function run(string $output = 'direct'){
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
            $layout = new self($this->layout, '', false);
            $layout->insert($this->extract())->run();
        }
        switch($output){
            case 'direct':
                break;

            case 'buffer':
                return ob_get_clean();

            case 'array':
                return $this->extract();

            default:
                break;
        }
    }

    public function path(string $path, string $extention = null, bool $folder = true): string{
        $path .= $extention ?? self::EXTENTION;
        if($folder == true && class_exists(\Krutush\Path)) //Remove require krutush/krutush
            $path = \Krutush\Path::get('template').'/'.$path;

        return $path;
    }

    public function _load(string $path, string $extention = null, bool $folder = true): self{
        $load = new self($path, $extention, $folder);
        $load->insert($this->extract())->run();
        $this->insert($load->extract());
        return $this;
    }

    public function _layout(string $path, string $extention = null, bool $folder = true): self{
        $this->layout = $this->path($path, $extention, $folder);
        return $this;
    }

    
    public function _exist(string $key): bool{
        return isset($this->data[$key]);
    }
    public function _x(string $key): bool{ return $this->_exist($key); }

    public function _exists(array $keys, bool $all = true){
        foreach($keys as $key){
            if($this->_exist($key)){
                if(!$all)
                    return true;
            }else{
                if($all)
                    return false;
            }
        }
        return $all;
    }
    public function _xs(array $keys, bool $all = true){ return $this->_exists($keys, $all); }

    public static function filter($data, string $key, string $value){
        switch($key){
            case 'type':
                switch($filters['type']){
                    case 'array':
                        if(!is_array($data))
                            return [$data];
                        break;
                    
                    case 'string':
                        if(!is_string($data))
                            return strval($data);
                        break;

                    case 'int':
                        if(!is_int($data))
                            return intval($data);
                }
                break;
        }
        return $data;
    }

    public function _get(string $key, array $filters = array()){
        if(!$this->_exist($key)){
            if(isset($filters['type'])){
                switch($filters['type']){
                    case 'array':
                        return array();
                    
                    case 'string':
                        return '';

                    case 'int':
                        return 0;
                }
            }
            return null;
        }else{
            $data = $this->data[$key];
            foreach($filters as $name => $value){
                $data = self::filter($data, $name, $value);
            }
            return $data;
        }
    }
    public function _(string $key, array $filters = array()){ return $this->_get($key, $filters); }
}
