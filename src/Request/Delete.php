<?php

namespace Krutush\Database\Request;

//TODO: Split in traits
//TODO: Add INTO
//TODO: Add UNION
use Krutush\Database\DatabaseException;

class Delete extends Data{
    protected $table;
    protected $where;

    public function from(string $table, bool $add = false): Delete{
        $this->table = ($add && $this->table ? $this->table.', ' : '').$table;
        return $this;
    }

    public function where(string $where, bool $add = false): Delete{
        $this->where = $add && $this->where ? '('.$this->where.') AND ('.$where.')' : $where;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new DatabaseException('Any table set');

        $sql = 'DELETE FROM '.$this->table.
        ($this->where ? ("\n".'WHERE '.$this->where) : '');
        return $sql;
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}