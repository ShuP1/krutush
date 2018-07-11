<?php

namespace Krutush\Database\Request;

use Krutush\Database\DatabaseException;

class Insert extends Data{
    protected $fields;
    protected $table;

    public function fields(array $fields = null, bool $add = false): Insert{
        $this->fields = $add ? array_merge($this->fields, $fields) : $fields;
        return $this;
    }

    public function into(string $table): Insert{
        $this->table = $table;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new \UnexpectedValueException('Any table set');

        return 'INSERT INTO '.$this->table."\n".
        '('.implode(', ', $this->fields).")\n".
        'VALUES ('.static::paramList(count($this->fields)).')';
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}