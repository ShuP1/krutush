<?php

namespace Krutush\Database;

use Krutush\Database\DatabaseException;

/**
 * Static is a table, Object is an row
 */
class Model{
    /** @var string */
    public const DATABASE = null;

    /** @var string */
    public const TABLE = null;

    /** @var array */ 
    public const FIELDS = [];
    /*[
        'id' => ['column' => 'idColumn', 'type' => 'int', 'not_null' => true, 'primary' => true, 'custom' => 'AUTO_INCREMENT'],
        'owner' => ['type' => 'int', 'foreign' => ['model' => UserModel::class, 'field' => 'id', 'on_delete' => 'cascade', 'on_update' => 'set null']]
    ]*/

    /** @var array */ 
    public const FOREIGNS = [];

    /** @var string */
    public const ID = 'id';

    /** 
     * @var array
     * @example ['id' => ['value' => 1, 'modified' => false]]
     */
    protected $fields = [];

    /**
     * Use wildcard in select queries
     * 
     * @var boll
     */
    public const WILDCARD = true;

    //Many be usefull  but not recommended
    public const FILTER = null;
    public const INNER = null;
    public const ORDER = null;


    /*=== MAGIC ===*/
    /**
     * Construct a model element with data
     * 
     * @param array $data to fill $fields
     * @param boolean $useColumns use Column's name or Field's name
     */
    public function __construct(array $data = [], bool $useColumns = false){
        foreach (static::getFields() as $field => $options) {
            $column = $useColumns ? static::getColumn($field) : $field;
            $value = static::convertField(isset($data[$column]) ? $data[$column] : (isset($options['default']) ? $options['default'] : null), $field);
            $this->fields[$field] = [
                'value' => $value,
                'modified' => false
            ];
        }
        foreach(static::getForeigns() as $foreign => $options){
            $this->fields[$foreign] = [
                'modified' => false
            ];
        }
    }

    //MAYBE: Save on destroy

    /**
     * Get data without reflection (may became heavy)
     * 
     * @param string $field value's key or foreign's key if start with _
     * @return mixed value, Model or null
     */
    public function __get(string $field){
        if(strlen($field) > 0){
            if($field[0] == '_')
                return $this->get(substr($field, 1), true);

            if(array_key_exists($field, $this->fields) && array_key_exists('value', $this->fields[$field]))
                return $this->fields[$field]['value'];
        }

		$trace = debug_backtrace();
        trigger_error(
            'Propriété non-définie via __get() : ' . $field .
            ' dans ' . $trace[0]['file'] .
            ' à la ligne ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __isset(string $field): bool{
        if(strlen($field) > 0){
            if($field[0] == '_')
                return $this->haveForeignOptions(substr($field, 1));

            if(array_key_exists($field, $this->fields) && array_key_exists('value', $this->fields[$field]))
                return true;
        }

        return false;
    }

    /**
     * Store data in $fields
     * 
     * @param string $field
     * @param mixed $value
     */
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
    
    /**
     * Set foreign's data
     *
     * @param string $field
     * @param Model|array|null $data
     * @return void
     */
    public function set(string $field, $data){
        if(!is_a($data, Model::class) && !is_array($data) && $data !== null)
            throw new DatabaseException('Set data must be a Model, array of Model or null');

        if((array_key_exists($field, static::FIELDS) && isset(static::getOptions($field)['foreign'])) || array_key_exists($field, static::FOREIGNS)){
            $this->fields[$field]['foreign'] = $data;
        }else{
            $trace = debug_backtrace();
            trigger_error(
                'Propriété non-définie via set() : ' . $field .
                ' dans ' . $trace[0]['file'] .
                ' à la ligne ' . $trace[0]['line'],
                E_USER_NOTICE);
        }
    }

    /**
     * Get foreign's data
     *
     * @param string $field
     * @param boolean $load must load data if can't find it
     * @return Model|array|null
     */
    public function get(string $field, bool $load = false){
		if(array_key_exists($field, $this->fields)){
            if(array_key_exists('foreign', $this->fields[$field]))
                return $this->fields[$field]['foreign'];
            else{
                if($load){
                    $foreign = static::getForeignOptions($field);

                    if(!isset($foreign['model']))
                        throw new DatabaseException('Any model for foreign in field '.$field);

                    $model = $foreign['model'];

                    if(!class_exists($model))
                        throw new DatabaseException('Can\'t find class '.$model.' for foreign in field '.$field);

                    $id = $this->{isset($foreign['for']) ? $foreign['for'] : $field};
                    $where = $model::getColumn(isset($foreign['field']) ? $foreign['field'] : $model::ID).' = ?';

                    $value = (isset($foreign['multiple']) && $foreign['multiple']) ? 
                        $model::all([$id], $where):
                        $model::first([$id], $where);
                
                    //MAYBE: Make nullable check

                    $this->fields[$field]['foreign'] = $value;
                    return $value;
                }
            }
        }

		$trace = debug_backtrace();
        trigger_error(
            'Propriété non-définie via get() : ' . $field .
            ' dans ' . $trace[0]['file'] .
            ' à la ligne ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    /**
     * Just get foreign's data or return null
     *
     * @param string $field
     * @return Model|array|null
     */
    public function tryGet(string $field){
		if(array_key_exists($field, $this->fields)){
            if(array_key_exists('foreign', $this->fields[$field]))
                return $this->fields[$field]['foreign'];
        }

        return null;
    }

    /*=== CREATE ===*/
    /**
     * Create Model from PDOStatement
     *
     * @param \PDOStatement $row
     * @param boolean $exception must throw exception ?
     * @return self|null
     */
    public static function fromRow(\PDOStatement $row, bool $exception = true): ?self{
        if($row->rowCount() < 1){
            if($exception)
                throw new \Exception('Create from Any Row');
            return null;
        }

        $data = $row->fetch();
        return new static($data, true);
    }

    /**
     * Create Model array from PDOStatement
     *
     * @param \PDOStatement $row
     * @param boolean $exception must throw exception ?
     * @return array
     */
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

    /**
     * Create Model array from data array
     *
     * @param array $data
     * @param boolean $useColumns see __construct
     * @return array
     */
    public static function fromData(array $data, bool $useColumns = false): array{
        $res = array();
        foreach($data as $element){
            $res[] = new static($element, $useColumns);
        }
        return $res;
    }


    /*=== CONST ===*/
    /**
     * Same as static::FIELDS with empty check
     *
     * @param boolean $exception
     * @return array
     */
    public static function getFields(bool $exception = true): array{
        if(empty(static::FIELDS) && $exception)
            throw new DatabaseException('FIELDS not set');
        
        return static::FIELDS;
    }

    /**
     * Get params for a specific field
     *
     * @param string $field
     * @return array
     */
    public static function getOptions(string $field): array{
        $fields = static::getFields();
        if(!isset($fields[$field])){
            if(array_key_exists($field, static::FOREIGNS))
                return static::FOREIGNS[$field];

            throw new DatabaseException('Can\'t find field : '.$field);
        }

        return $fields[$field];
    }

    /**
     * Same as getFields for static::FOREIGNS
     *
     * @param boolean $exception
     * @return array
     */
    public static function getForeigns(bool $exception = false): array{
        if(empty(static::FOREIGNS) && $exception)
            throw new DatabaseException('FOREIGNS not set');
        
        return static::FOREIGNS;
    }

    /**
     * Get FIELDS foreign options or FOREIGNS options
     *
     * @param string $field
     * @return array
     */
    public static function getForeignOptions(string $field): array{
        $fields = static::getFields();
        if(isset($fields[$field]) && isset($fields[$field]['foreign']))
            return is_array($fields[$field]['foreign']) ? $fields[$field]['foreign'] : ['model' => $fields[$field]['foreign']];

        $foreigns = static::getForeigns();
        if(!isset($foreigns[$field]))
            throw new DatabaseException('Not a foreign field');

        return is_array($foreigns[$field]) ? $foreigns[$field] : ['model' => $foreigns[$field], 'for' => static::ID];
    }

    /**
     * Check in field have FIELDS foreign options or FOREIGNS options
     *
     * @param string $field
     * @return bool
     */
    public static function haveForeignOptions(string $field): bool{
        $fields = static::getFields(false);
        if($field == null)
            return false;

        if(isset($fields[$field]) && isset($fields[$field]['foreign']))
            return true;

        $foreigns = static::getForeigns();
        return isset($foreigns[$field]);
    }

    /**
     * Convert field to column
     *
     * @param string $field
     * @param boolean $sql add table name and quote
     * @return string
     */
    public static function getColumn(string $field, bool $sql = false): string{
        $options = static::getOptions($field);
        $column = isset($options['column']) ? $options['column'] : $field;
        return $sql ? '`'.static::TABLE.'`.`'.$column.'`' : $column;
    }

    /**
     * Get table ID (for find and findOrFail)
     *
     * @param boolean $sql add table name and quote
     * @return string
     */
    public static function getID(bool $sql = false): string{
        return static::getColumn(static::ID, $sql);
    }

    /**
     * Get column's names
     *
     * @param boolean $sql add table name and quote
     * @return array
     */
    public static function getColumns(bool $sql = true): array{
        $fields = static::getFields();
        $columns = [];
        foreach ($fields as $field => $options) {
            $columns[] = static::getColumn($field, $sql);
        }
        return $columns;
    }

    /**
     * Same as getColumns but only with 'primary' => true fields
     *
     * @param boolean $sql add table name and quote
     * @return array
     */
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

    /**
     * Same as getColumns but only with 'modified' => true $this->fields
     *
     * @param boolean $sql add table name and quote
     * @return array
     */
    public function getModifiedColumns(bool $sql = true): array{
        $fields = static::getFields();
        $columns = [];
        foreach ($fields as $field => $options) {
            if(isset($this->fields[$field]['modified']) && $this->fields[$field]['modified']){
                $column = static::getColumn($field);
                $columns[] = $sql ? '`'.static::TABLE.'`.`'.$column.'`' : $column;
            }
        }
        return $columns;
    }

    /**
     * Get fields values (reverse of static::fromData)
     *
     * @return array
     */
    public function getValues(){
        $values = [];
        foreach ($this->fields as $field => $data) {
            if(array_key_exists('value', $data))
                $values[] = $data['value'];
        }
        return $values;
    }

    /**
     * Same as getValues but only with 'modified' => true $this->fields
     *
     * @return array
     */
    public function getModifiedValues(){
        $values = [];
        foreach ($this->fields as $field => $data) {
            if(isset($data['modified']) && $data['modified'])
                $values[] = $data['value'];

        }
        return $values;
    }

    /**
     * Remove all modified flags
     *
     * @return void
     */
    public function unmodify(){
        foreach($this->fields as $field => $data){
            if(isset($data['modified']))
                $this->fields[$field]['modified'] = false;
        }
    }

    /**
     * Same as getValues but only with 'primary' => true fields
     *
     * @return array
     */
    public function getPrimaryValues(){
        $values = [];
        foreach ($this->fields as $field => $data) {
            $options = static::getOptions($field);
            if(isset($options['primary']) && $options['primary']) $values[] = $data['value'];
        }
        return $values;
    }

    /**
     * Convert input to sql valid value
     *
     * @param mixed $data
     * @param string $field
     * @return mixed
     */
    protected static function convertField($data, string $field){
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
                    case 'date':
                        $data = (is_a($data, \DateTime::class) ? $data : new \DateTime(strval($data)))->format('Y-m-d');
                        break;
                    case 'time':
                        $data = (is_a($data, \DateTime::class) ? $data : new \DateTime(strval($data)))->format('H:i:s');
                        break;
                    case 'datetime':
                        $data = (is_a($data, \DateTime::class) ? $data : new \DateTime(strval($data)))->format('Y-m-d H:i:s');
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
    /**
     * Create Request\Select from Model
     *
     * @return Request\Select
     */
    public static function select(): Request\Select{
        $req = Connection::get(static::DATABASE)
            ->select()->from(static::TABLE);

        if(!static::WILDCARD)
            $req = $req->fields(static::getColumns());
            

        if(static::INNER != null)
            $req = $req->join(static::INNER);

        if(static::FILTER != null)
            $req = $req->where(static::FILTER);

        if(static::ORDER != null)
            $req = $req->orderby(static::ORDER);

        return static::prepare($req);
    }

    /**
     * Create Request\Insert (without data) from Model
     *
     * @return Request\Insert
     */
    public static function insert(): Request\Insert{
        $req = Connection::get(static::DATABASE)
            ->insert(static::getColumns())
            ->into(static::TABLE);

        return $req;
    }

    /**
     * add values to insert and run it
     *
     * @param boolean $forceID must update id value
     */
    public function runInsert(bool $forceID = true){
        $insert = static::insert();
        $res = $insert->run($this->getValues());
        if($forceID && static::ID != null)
            $this->{static::ID} = Connection::get(static::DATABASE)->getLastInsertID();

        return $res;
    }

    /**
     * Change value of modified fields in database
     *
     * @return Model
     */
    public function runUpdate(){
        $req = Connection::get(static::DATABASE)
            ->update(static::getModifiedColumns())
            ->table(static::TABLE)
            ->where(implode(' AND ', array_map(function($field){ return $field.' = ?'; }, static::getPrimaryColumns())))
            ->run(array_merge($this->getModifiedValues(true), $this->getPrimaryValues()));
        $this->unmodify();
        return $this;
    }

    /**
     * Create Request\Create from Model
     *
     * @return Request\Create
     */
    public static function create(): Request\Create{
        $req = Connection::get(static::DATABASE)
            ->create(static::TABLE);

        foreach (static::getFields() as $field => $options) {
            $column = static::getColumn($field);
            $req->column(
                $column,
                $options['type'],
                isset($options['lenght']) ? $options['lenght'] : null,
                isset($options['not_null']) && $options['not_null'],
                isset($options['custom']) ? $options['custom'] : null);
            
            if(isset($options['primary']) && $options['primary'])
                $req->primary($column);

            if(isset($options['unique']) && $options['unique'])
                $req->unique($column);

            $index = false;

            if(isset($options['foreign'])){
                $foreign = $options['foreign'];

                $model = null;

                if(is_array($foreign)){
                    if(!isset($foreign['model']))
                        throw new DatabaseException('Any model for foreign in field '.$field);

                    $model = $foreign['model'];
                }else{
                    $model = $foreign;
                }
                if(!class_exists($model))
                    throw new DatabaseException('Can\'t find class '.$model.' for foreign in field '.$field);

                $req->foreign($column, $model::TABLE,
                    $model::getColumn(isset($foreign['field']) ? $foreign['field'] : $model::ID),
                    isset($foreign['on_delete']) ? $foreign['on_delete'] : null,
                    isset($foreign['on_update']) ? $foreign['on_update'] : null);

                $index = true;
            }

            if(isset($options['index']) ? $options['index'] : $index)
                $req->index($column);
        }

        return $req;
    }

    /**
     * Create Request\Drop from Model
     *
     * @return Request\Drop
     */
    public static function drop(): Request\Drop{
        return Connection::get(static::DATABASE)
            ->drop(static::TABLE);
    }

    /* Do advanced customuzation here */
    /**
     * Modify default select request
     *
     * @param Request\Select $req
     * @return Request\Select
     */
    protected static function prepare(Request\Select $req): Request\Select{ return $req; }

    /**
     * Really used only for next functions
     *
     * @param array $values
     * @param string $where
     * @return PDOStatement
     */
    public static function runSelect(array $values = null, string $where = null): \PDOStatement{
        $req = static::select();

        if(isset($where))
            $req = $req->where($where, true);

        return $req->run($values);
    }

    /**
     * Get first row than match $where with $values or null
     *
     * @param array $values
     * @param string $where
     * @return self|null
     */
    public static function first(array $values = null, string $where = null): ?self{
        return static::fromRow(static::runSelect($values, $where), false);
    }

    /**
     * Same as first but throw exception on null
     *
     * @param array $values
     * @param string $where
     * @return self
     */
    public static function firstOrFail(array $values = null, string $where = null): self{
        return static::fromRow(static::runSelect($values, $where));
    }

    /**
     * Get all rows than match $where with $values (may be empty)
     *
     * @param array $values
     * @param string $where
     * @return array
     */
    public static function all(array $values = null, string $where = null): array{
        return static::fromRowAll(static::runSelect($values, $where), false);
    }

    /**
     * Same as all but throw exception on empty
     *
     * @param array $values
     * @param string $where
     * @return array
     */
    public static function allOrFail(array $values = null, string $where = null): array{
        return static::fromRowAll(static::runSelect($values, $where));
    }

    /**
     * Check if at least one row exists
     *
     * @param array $values
     * @param string $where
     * @return boolean
     */
    public static function exists(array $values = null, string $where = null): bool{
        return static::first($values, $where) !== null;
    }

    /**
     * Count row than match $where with $values
     *
     * @param array $values
     * @param string $where
     * @return integer
     */
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

    /**
     * Use static:ID to get row
     *
     * @param mixed $id int is a good idea
     * @return self|null
     */
    public static function find($id): ?self{
         return static::first(array($id), static::getID().' = ?');
    }

    /**
     * Same as find but throw exception on null
     *
     * @param mixed $id int is a good idea
     * @return self
     */
    public static function findOrFail($id): self{
         return static::firstOrFail(array($id), static::getID().' = ?');
    }

    /**
     * Use static:ID to get rows
     *
     * @param array $ids array(int) is a good idea
     * @return array|null
     */
    public static function finds(array $ids): ?array{
         return static::all($ids, static::getID().' IN ( '.str_repeat('?, ', count($ids)-1).'? )');
    }

    /**
     * Same as find but throw exception on null
     *
     * @param array $ids array(int) is a good idea
     * @return array
     */
    public static function findsOrFail(array $ids): array{
         return static::allOrFail($ids, static::getID().' IN ( '.str_repeat('?, ', count($ids)-1).'? )');
    }

    /**
     * Preload foreign Model for a group of Models
     *
     * @param array $models
     * @param string $field
     * @return array updated models
     */
    public static function load(array $models, string $field): array{
        if(!empty($models)){
            $foreign = static::getForeignOptions($field);

            if(!isset($foreign['model']))
                throw new DatabaseException('Any model for foreign in field '.$field);

            $model = $foreign['model'];

            if(!class_exists($model))
                throw new DatabaseException('Can\'t find class '.$model.' for foreign in field '.$field);

            $ids = [];
            foreach ($models as $current) {
                $ids[] = $current->{isset($foreign['for']) ? $foreign['for'] : $field};
            }
            $ids = array_values(array_unique($ids));
            $foreigns = [];
            foreach($model::all($ids, $model::getColumn(isset($foreign['field']) ? $foreign['field'] : $model::ID).' IN ( '.str_repeat('?, ', count($ids)-1).'? )') as $current){
                $cid = $current->{isset($foreign['field']) ? $foreign['field'] : $model::ID};
                if(isset($foreign['multiple']) && $foreign['multiple'])
                    $foreigns[$cid][] = $current;
                else
                    $foreigns[$cid] = $current;
            }

            foreach ($models as &$current) {
                $id = $current->{isset($foreign['for']) ? $foreign['for'] : $field};
                if(isset($foreigns[$id]))
                    $current->set($field, $foreigns[$id]);
                else if(!isset($foreign['nullable']) || !$foreign['nullable'])
                    var_dump($ids);//throw new DatabaseException('Null foreign model');
                else
                    $current->set($field, null);
            }
        }

        return $models;
    }

    /**
     * Preload multiple foreign Model with recursivity for a group of Models
     *
     * @param array $models
     * @param array $fields
     * @return array updated models
     */
    public static function loads(array $models, array $fields): array{
        foreach($fields as $field => $data){
            $subfields = [];
            if(is_array($data)){
                $subfields = $data;
            }else{
                $field = $data;
            }
            $models = static::load($models, $field);
            if(!empty($subfields)){
                $submodels = [];
                foreach($models as $model){
                    if($model->tryGet($field) != null)
                        $submodels[] = $model->get($field);
                }
                if(!empty($submodels)){
                    $submodels[0]::loads($submodels, $subfields);
                }
            }
        }
        return $models;
    }
}