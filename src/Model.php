<?php

namespace Krutush\Database;

use Krutush\Database\DatabaseException;

//TODO extends
//TODO add model links

class Model{
    /** @var string */
    public const DATABASE = null;

    /** @var string */
    public const TABLE = null;

    /** @var array 
     * @example ['id' => ['column' => 'idColumn', 'type' => 'int', 'not_null' => true, 'primary' => true, 'custom' => 'AUTO_INCREMENT']] */
    public const FIELDS = [];

    /** @var array
     * @example ['id' => ['value' => 1, 'modified' => false]] */
    protected $fields = [];

    public const FILTER = null;
    public const INNER = null; //TODO: Manager OneToOne, OneToMany, ...
    public const ORDER = null;


    /*=== MAGIC ===*/
    public function __construct(array $data = [], bool $useColumns = false){
        foreach (static::getFields() as $field => $options) {
            $column = $useColumns ? static::getColumn($field) : $field;
            $value = static::convertField(isset($data[$column]) ? $data[$column] : (isset($options['default']) ? $options['default'] : null), $field);
            $this->fields[$field] = [
                'value' => $value,
                'modified' => false
            ];
        }
    }

    //MAYBE: Save on destroy

    public function __get(string $field){
		if(array_key_exists($field, $this->fields))
            return $this->fields[$field]['value'];

		$trace = debug_backtrace();
        trigger_error(
            'Propriété non-définie via __get() : ' . $field .
            ' dans ' . $trace[0]['file'] .
            ' à la ligne ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
	}

    public function __set(string $field, $value){
        if(array_key_exists($field, static::FIELDS)){
            $this->fields[$field] = [
                'value' => static::convertField($value, $field),
                'modified' => true
            ];
        }else{
            $trace = debug_backtrace();
            trigger_error(
                'Propriété non-définie via __set() : ' . $field .
                ' dans ' . $trace[0]['file'] .
                ' à la ligne ' . $trace[0]['line'],
                E_USER_NOTICE);
        }
	}


    /*=== CREATE ===*/
    public static function fromRow(\PDOStatement $row, bool $exception = true): ?self{
        if($row->rowCount() < 1){
            if($exception)
                throw new \Exception('Create from Any Row');
            return null;
        }

        $data = $row->fetch();
        return new static($data, true);
    }

    public static function fromRowAll(\PDOStatement $row, bool $exception = true): array{
        if($row->rowCount() < 1){
            if($exception)
                throw new \Exception('Create from Any Row');
            return [];
        }

        $res = array();
        while($data = $row->fetch()){
            $res[] = new static($data, true);
        }
        return $res;
    }

    public static function fromData($data, bool $useColumns = false): array{
        $res = array();
        foreach($data as $element){
            $res[] = new static($element, $useColumns);
        }
        return $res;
    }


    /*=== CONST ===*/
    public static function getFields(bool $exception = false): array{
        if(empty(static::$FIELDS) && $exception)
            throw new DatabaseException('FIELDS not set');
        
        return static::FIELDS;
    }

    public static function getOptions(string $field): array{
        $fields = static::getFields();
        if(!isset($fields[$field]))
            throw new DatabaseException('Can\'t find field : '.$field);

        return $fields[$field];
    }

    public static function getColumn(string $field): string{
        $options = static::getOptions($field);
        return isset($options['column']) ? $options['column'] : $field;
    }

    public static function getColumns(bool $sql = true): array{
        $fields = static::getFields();
        $columns = [];
        foreach ($fields as $field => $options) {
            $column = static::getColumn($field);
            $columns[] = $sql ? '`'.static::TABLE.'`.`'.$column.'`' : $column;
        }
        return $columns;
    }

    public static function getPrimaryColumns(bool $sql = true): array{
        $fields = static::getFields();
        $columns = [];
        foreach ($fields as $field => $options) {
            if(isset($options['primary']) && $options['primary']){
                $column = static::getColumn($field);
                $columns[] = $sql ? '`'.static::TABLE.'`.`'.$column.'`' : $column;
            }
        }
        return $columns;
    }

    public function getModifiedColumns(bool $sql = true): array{
        $fields = static::getFields();
        $columns = [];
        foreach ($fields as $field => $options) {
            if($this->fields[$field]['modified']){
                $column = static::getColumn($field);
                $columns[] = $sql ? '`'.static::TABLE.'`.`'.$column.'`' : $column;
            }
        }
        return $columns;
    }

    public function getValues(){
        $values = [];
        foreach ($this->fields as $field => $data) {
            $values[] = $data['value'];
        }
        return $values;
    }

    public function getModifiedValues(){
        $values = [];
        foreach ($this->fields as $field => $data) {
            if($data['modified']) $values[] = $data['value'];
        }
        return $values;
    }

    public function getPrimaryValues(){
        $values = [];
        foreach ($this->fields as $field => $data) {
            $options = static::getOptions($field);
            if(isset($options['primary']) && $options['primary']) $values[] = $data['value'];
        }
        return $values;
    }

    protected static function convertField($data, $field){
        $options = static::getOptions($field);
        if(is_null($data)){
            if(isset($options['not_null']) && $options['not_null'] == true)
                throw new DatabaseException('Can\'t set null to NOT NULL field : '.$field);
        }else{
            if(isset($options['type'])){
                switch(strtolower($options['type'])){
                    case 'int':
                        $data = intval($data); //MAYBE: E_NOTICE on strange types
                        break;
                    case 'char':
                    case 'varchar':
                    case 'text':
                        $data = strval($data); //MAYBE: E_NOTICE on strange types
                        if(isset($options['lenght']) && strlen($data) > $options['lenght'])
                            throw new DatabaseException('data is to long in field : '.$field);
                        break;
                    case 'bit':
                        $data = boolval($data); //MAYBE: E_NOTICE on strange types
                        break;
                    default:
                        throw new DatabaseException('unknown type in field : '.$field);
                        break;
                }
            }

            return $data;
        }
    }


    /*=== QUERIES ===*/
    public static function select(): Request\Select{
        $req = Connection::get(static::DATABASE)
            ->select(static::getColumns())
            ->from(static::TABLE);

        if(static::INNER != null)
            $req = $req->join(static::INNER);

        if(static::FILTER != null)
            $req = $req->where(static::FILTER);

        if(static::ORDER != null)
            $req = $req->orderby(static::ORDER);

        return static::prepare($req);
    }

    public static function insert(): Request\Insert{
        $req = Connection::get(static::DATABASE)
            ->insert(static::getColumns())
            ->into(static::TABLE);

        return $req;
    }

    public function runInsert(){
        return static::insert()->run($this->getValues());
    }

    public function runUpdate(){
        $req = Connection::get(static::DATABASE)
            ->update(static::getModifiedColumns())
            ->table(static::TABLE)
            ->where(implode(' AND ', array_map(function($field){ return $field.' = ?'; }, static::getPrimaryColumns())))
            ->run(array_merge($this->getModifiedValues(), $this->getPrimaryValues()));
    }

    public static function create(): Request\Create{
        $req = Connection::get(static::DATABASE)
            ->create(static::TABLE);

        foreach (static::getFields() as $field => $options) {
            $req->column(
                static::getColumn($field),
                $options['type'],
                isset($options['lenght']) ? $options['lenght'] : null,
                isset($options['not_null']) && $options['not_null'],
                isset($options['primary']) && $options['primary'],
                isset($options['unique']) && $options['unique'],
                isset($options['custom']) ? $options['custom'] : null);
        }

        return $req;
    }

    public static function drop(){
        return Connection::get(static::DATABASE)
            ->drop(static::TABLE);
    }

    /* Do advanced customuzation here */
    protected static function prepare($req){ return $req; }

    public static function runSelect(array $values = null, string $where = null){
        $req = static::select();

        if(isset($where))
            $req = $req->where($where, true);

        return $req->run($values);
    }

    public static function first(array $values = null, string $where = null): ?self{
        return static::fromRow(static::runSelect($values, $where), false);
    }

    public static function firstOrFail(array $values = null, string $where = null): ?self{
        return static::fromRow(static::runSelect($values, $where));
    }

    public static function all(array $values = null, string $where = null): array{
        return static::fromRowAll(static::runSelect($values, $where), false);
    }

    public static function allOrFail(array $values = null, string $where = null): array{
        return static::fromRowAll(static::runSelect($values, $where));
    }

    public static function exists(array $values = null, string $where = null): bool{
        return static::first($values, $where) !== null;
    }

    public static function count(array $values = null, string $where = null): int{
        $req = static::select();
        $req->fields(['COUNT(*) as count']);

        if(isset($where))
            $req = $req->where($where, true);

        $data = $req->run($values)->fetch();
        if(!isset($data['count']))
            return 0;

        return $data['count'];
    }


    /* TODO: Manager ids
    public static function find($id) {
         return static::first(array($id), (static::getID().' = ?'));
    }

    public static function findOrFail($id) {
         return static::firstOrFail(array($id), (static::getID().' = ?'));
    }*/
}