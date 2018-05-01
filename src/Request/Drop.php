<?php

namespace Krutush\Database\Request;

use Krutush\Database\Database;
use Krutush\Database\DatabaseException;

class Drop extends Request{
    protected $table;

    public function table(string $table): Drop{
        $this->table = $table;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new DatabaseException('Any table set');

        return 'DROP TABLE `'.$this->table.'`';
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}