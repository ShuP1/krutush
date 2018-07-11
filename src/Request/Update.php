<?php

namespace Krutush\Database\Request;

use Krutush\Database\DatabaseException;

/** UPDATE */
class Update extends Data{
    /** @var array */
    protected $fields;

    /** @var string */
    protected $table;

    /** @var array */
    protected $where;

    public function fields(array $fields = null, bool $add = false): Update{
        $this->fields = $add ? array_merge($this->fields, $fields) : $fields;
        return $this;
    }

    public function table(string $table): Update{
        $this->table = $table;
        return $this;
    }

    /**
     * @param string|array $where
     * @param boolean $add
     * @return Update
     */
    public function where($where, bool $add = false): Update{
        $where = is_array($where) ? $where : [$where];
        $this->where = $add && $this->where ? array_merge($this->where, $where) : $where;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new \UnexpectedValueException('Any table set');

        return 'UPDATE '.$this->table."\n".
        'SET '.static::toParams($this->fields)."\n".
        ($this->where ? 'WHERE '.static::combineParams($this->where) : '');
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}