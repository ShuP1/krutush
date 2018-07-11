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

    /**
     * @param string|array $where
     * @param boolean $add
     * @return Delete
     */
    public function where($where, bool $add = false): Delete{
        $where = is_array($where) ? $where : [$where];
        $this->where = $add && $this->where ? array_merge($this->where, $where) : $where;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new \UnexpectedValueException('Any table set');

        $sql = 'DELETE FROM '.$this->table.
        ($this->where ? ("\n".'WHERE '.static::combineParams($this->where)) : '');
        return $sql;
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}