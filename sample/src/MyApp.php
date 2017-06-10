<?php

namespace Exemple;

use Krutush\App;

class MyApp extends App{
    public function __construct(array $data = array()){
        if(!isset($data['path']['root']))
            $data['path']['root'] = dirname(__DIR__);
        if(!isset($data['app']['namespace']))
            $data['app']['namespace'] = __NAMESPACE__.'\\Controller\\';
        if(!isset($data['app']['controller']))
            $data['app']['controller'] = '';
        parent::__construct($data);
    }
}