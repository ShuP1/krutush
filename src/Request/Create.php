<?php

namespace Krutush\Database\Request;

use Krutush\Database\Database;
use Krutush\Database\DatabaseException;

class Create extends Request{
    protected $table;
    protected $columns = [];
    protected $primary = [];
    protected $unique = [];

    public function table(string $table): Create{
        $this->table = $table;
        return $this;
    }

    public function column(string $name, string $type, int $lenght = null, bool $not_null = false, bool $primary = false, bool $unique = false, string $more = null): Create{
        $this->columns[] = '`'.$name.'` '.$type.($lenght ? '('.$lenght.')' : '').($not_null ? ' NOT NULL' : '').(isset($more) ? ' '.$more : '');
        if($primary)
            $this->primary[] = '`'.$name.'`';

        if($unique)
            $this->unique[$name] = [$name];
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new DatabaseException('Any table set');

        if(empty($this->columns))
            throw new DatabaseException('Any columns set');

        $uniques = [];
        foreach ($this->unique as $name => $columns) {
            $uniques[] = 'CONSTRAINT UC_'.ucfirst(strtolower($name)).' UNIQUE ('.implode(', ', $columns).')';
        }

        return 'CREATE TABLE `'.$this->table.'`('."\n".
        $sql = implode(",\n",
            array_merge(
                $this->columns,
                (empty($this->primary) ? [] : [
                    'CONSTRAINT PK_'.ucfirst(strtolower(strtok($this->table, ' '))).' PRIMARY KEY ('.implode(', ', $this->primary).')'
                ]),
                $uniques
            )
        )."\n)";
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}