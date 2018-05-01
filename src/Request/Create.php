<?php

namespace Krutush\Database\Request;

use Krutush\Database\Database;
use Krutush\Database\DatabaseException;

class Create extends Request{
    protected $table;
    protected $columns = [];
    protected $primary = [];

    public function table(string $table): Create{
        $this->table = $table;
        return $this;
    }

    public function column(string $name, string $type, int $lenght = null, bool $not_null = false, bool $primary = false, string $more = null): Create{
        $this->columns[] = '`'.$name.'` '.$type.($lenght ? '('.$lenght.')' : '').($not_null ? ' NOT NULL' : '').(isset($more) ? ' '.$more : '');
        if($primary)
            $this->primary[] = '`'.$name.'`';
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new DatabaseException('Any table set');

        if(empty($this->columns))
            throw new DatabaseException('Any columns set');

        return 'CREATE `'.$this->table.'`('."\n".
        $sql = implode(",\n",
            array_merge($this->columns, (empty($this->primary) ? [] : [
                'CONSTRAINT PK_'.ucfirst(strtolower(strtok($this->table, ' '))).' PRIMARY KEY ('.implode(', ', $this->primary).')'
            ]))
        )."\n)";

        //TODO: foreign keys
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}