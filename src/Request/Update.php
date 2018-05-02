<?php

namespace Krutush\Database\Request;

use Krutush\Database\DatabaseException;

class Update extends Data{
    protected $fields;
    protected $table;
    protected $where;

    public function fields(array $fields = null, bool $add = false): Update{
        $this->fields = $add ? array_merge($this->fields, $fields) : $fields;
        return $this;
    }

    public function table(string $table): Update{
        $this->table = $table;
        return $this;
    }

    public function where(string $where, bool $add = false): Update{
        $this->where = $add && $this->where ? '('.$this->where.') AND ('.$where.')' : $where;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new DatabaseException('Any table set');

        return 'UPDATE `'.$this->table."`\n".
        'SET '.implode(', ', array_map(function($field){ return $field.' = ?'; }, $this->fields))."\n".
        (isset($this->where) ? ('WHERE '.$this->where) : '');
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}