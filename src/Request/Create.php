<?php

namespace Krutush\Database\Request;

use Krutush\Database\Database;
use Krutush\Database\DatabaseException;

class Create extends Request{
    protected $table;
    protected $columns = [];
    protected $primary = [];
    protected $unique = [];
    protected $index = [];
    protected $foreign = [];

    public function table(string $table): Create{
        $this->table = $table;
        return $this;
    }

    public function column(string $name, string $type, int $lenght = null, bool $not_null = false, string $more = null): Create{
        $this->columns[] = compact('name', 'type', 'lenght', 'not_null', 'more');
        return $this;
    }

    public function primary(string $name): Create{
        $this->primary[] = $name;
        return $this;
    }

    public function unique(string $name, array $columns = null): Create{
        $this->unique[$name] = $columns ?? [$name];
        return $this;
    }

    public function index(string $name, array $columns = null): Create{
        $this->index[$name] = $columns ?? [$name];
        return $this;
    }

    public function foreign(string $name, string $table, string $column, string $on_delete = null, string $on_update = null): Create{ //TODO: complex foreign
        $this->foreign[$name] = compact('name', 'table', 'column', 'on_delete', 'on_update');
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new \UnexpectedValueException('Any table set');

        if(empty($this->columns))
            throw new \UnexpectedValueException('Any columns set');

        $columns = [];
        foreach($this->columns as $column){
            $columns[] = $column['name'].' '.$column['type'].($column['lenght'] ? '('.$column['lenght'].')' : '').($column['not_null'] ? ' NOT NULL' : '').(isset($column['more']) ? ' '.$column['more'] : '');
        }

        $uniques = [];
        foreach ($this->unique as $name => $columns) {
            $uniques[] = 'CONSTRAINT `UQ_'.ucfirst(strtolower($this->table)).'_'.ucfirst(strtolower($name)).'` UNIQUE ('.implode(', ', $columns).')';
        }

        $indexs = [];
        foreach ($this->index as $name => $columns) {
            $indexs[] = 'INDEX `ID_'.ucfirst(strtolower($this->table)).'_'.ucfirst(strtolower($name)).'` ('.implode(', ', $columns).')';
        }

        $foreigns = [];
        foreach ($this->foreign as $name => $options) {
            $foreigns[] = 'CONSTRAINT `FK_'.ucfirst(strtolower($this->table)).'_'.ucfirst(strtolower($name)).'` FOREIGN KEY (`'.$options['name'].'`) REFERENCES `'.$options['table'].'` (`'.$options['column'].'`)'.
            (isset($options['on_delete']) ? 'ON DELETE '.$options['on_delete'] : '').
            (isset($options['on_update']) ? 'ON UPDATE '.$options['on_update'] : '');
        }

        return 'CREATE TABLE '.$this->table.'('."\n".
        implode(",\n",
            array_merge(
                $columns,
                (empty($this->primary) ? [] : [
                    'CONSTRAINT `PK_'.ucfirst(strtolower(strtok($this->table, ' '))).'` PRIMARY KEY ('.implode(', ', $this->primary).')'
                ]),
                $indexs,
                $uniques,
                $foreigns
            )
        )."\n)";
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}