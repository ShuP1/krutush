<?php

namespace Krutush\Database\Request;

/** I'm the WHERE */
class Data extends Request{
    /** @var array */
    protected $values;

    public function values(array $values, bool $add = false){
        if($add)
            $this->values = array_merge($this->values, $values);
        else
            $this->values = $values;
        return $this;
    }

    public function execute(string $sql, array $values = null){
        return parent::execute($sql, $values ? ($this->values ? array_merge($this->values, $values) : $values) : $this->values);
    }
}