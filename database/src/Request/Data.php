<?php

namespace Krutush\Database\Request;

class Data extends Request{
    protected $values;

    public function values(array $values, bool $add = false){
        if($add){
            $this->values = array_merge($this->values, $values);
        }else{
            $this->values = $values;
        }
        return $this;
    }

    public function execute(string $sql, array $values = null){
        $values = $values ? ($this->values ? array_merge($this->values, $values) : $values) : $this->values;
        return parent::execute($sql, $values);
    }
}