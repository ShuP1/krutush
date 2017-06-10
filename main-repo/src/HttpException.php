<?php

namespace Krutush;

class HttpException extends \Exception{
    private $httpCode;

    public function __construct($httpCode = 500, $message = null){
        $this->httpCode = $httpCode;
        parent::__construct($message);
    }

    public function getHttpCode(){
        return $this->httpCode;
    }
}