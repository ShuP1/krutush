<?php

namespace Krutush\Database\Request;

//TODO: Split in traits
//TODO: Add INTO
//TODO: Add UNION
use Krutush\Database\DatabaseException;

class Select extends Data{
    /** @var array */
    protected $fields;

    /** @var string */
    protected $table;

    /** @var array */
    protected $where;

    /** @var string */
    protected $group;
    protected $order;
    protected $limit;
    protected $offset;
    protected $joins;

    public function fields(array $fields = null, bool $add = false): Select{
        $this->fields = $add ? array_merge($this->fields, $fields) : $fields;
        return $this;
    }

    public function from(string $table, bool $add = false): Select{
        $this->table = ($add && $this->table ? $this->table.', ' : '').$table;
        return $this;
    }

    public function join(string $joins, string $type = 'INNER', bool $add = false): Select{
        if(!in_array($type, array('INNER', 'LEFT', 'RIGHT')))
            throw new \InvalidArgumentException('Unknown JOIN type');
        $this->joins = ($add && $this->joins ? $this->joins."\n" : '').$type.' JOIN '.$joins;
        return $this;
    }

    /**
     * @param string|array $where
     * @param boolean $add
     * @return Select
     */
    public function where($where, bool $add = false): Select{
        $where = is_array($where) ? $where : [$where];
        $this->where = $add && $this->where ? array_merge($this->where, $where) : $where;
        return $this;
    }

    public function groupby(string $group): Select{
        $this->group = $group;
        return $this;
    }

    public function orderby(string $order): Select{
        $this->order = $order;
        return $this;
    }

    public function limit(string $limit): Select{
        $this->limit = $limit;
        return $this;
    }

    public function offset(string $offset): Select{
        $this->offset = $offset;
        return $this;
    }

    public function sql(){
        if(!isset($this->table))
            throw new \UnexpectedValueException('Any table set');

        $fields = '*';
        if(isset($this->fields)){
            $lines = [];
            foreach($this->fields as $key => $value){
                $lines[] = $value.(is_string($key) ? ' '.$key : '');
            }
            $fields = implode(', ', $lines);
        }

        $sql = 'SELECT '.$fields.
        "\n".'FROM '.$this->table.
        ($this->joins ? ("\n".$this->joins) : '').
        ($this->where ? ("\n".'WHERE '.static::combineParams($this->where)) : '').
        ($this->group ? ("\n".'GROUP BY '.$this->group) : '').
        ($this->order ? ("\n".'ORDER BY '.$this->order) : '').
        ($this->limit ? ("\n".'LIMIT '.$this->limit) : '').
        ($this->offset ? (($this->limit ? '' : "\n".'LIMIT 18446744073709551615').' OFFSET '.$this->offset) : '');
        return $sql;
    }

    public function run(array $values = null){
        return parent::execute($this->sql(), $values);
    }
}