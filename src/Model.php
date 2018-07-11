<?php

namespace Krutush\Database;

use Krutush\Database\Request\Request;

/**
 * Basic Active Record model: a class represent a table and an instance represent a row
 */
class Model { //TODO: Complex (multiple keys) foreign
    /**
     * Model instance values
     *
     * @example foreach field [value: mixed, foreign: Model, modified: bool]
     * @var array
     */
    protected $fields = [];

    /*=== CONSTANTS ===*/
    /** @var string */
    public const WORD_SEPARATOR = '_';

    /** @var array */
    public const DATA_TYPES = [ //MAYBE: E_NOTICE on strange types
        'int' => [
            'convert' => 'intval'
        ],
        'integer' => 'int',
        'bigint' => 'int',
        'smallint' => 'int',
        'bool' => [
            'data_type' => 'bit',
            'convert' => 'boolval'
        ],
        'boolean' => 'bool',
        'varchar' => [
            'length' => 'strlen',
            'convert' => 'strval'
        ],
        'date' => [
            'convert' => 'Krutush\Database\TypeHelper::dateConvert'
        ],
        'time' => [
            'convert' => 'Krutush\Database\TypeHelper::timeConvert'
        ],
        'datetime' => [
            'convert' => 'Krutush\Database\TypeHelper::datetimeConvert'
        ],
        'timestamp' => 'datetime'
    ];

    public static function getDataType(string $type): array{
        $data = static::DATA_TYPES[$type] ?? ['convert' => 'strval'];
        return is_string($data) ? static::getDataType($data) : $data;
    }

    /**
     * Use wildcard in select queries
     *
     * @var bool
     */
    public const WILDCARD = true;

    /** @var string */
    public const DATABASE = null;

    /**
     * Get database name for static::DATABASE (useless: just in case, we became smarter in the future)
     *
     * @return string
     */
    public static function getDatabase(): ?string{
        return static::DATABASE;
    }


    /** @var string */
    public const TABLE = null;

    /** @var string */
    public const CLASS_PREFIX = 'Model\\';

    /** @var string */
    public const CLASS_SUFFIX = 'Model';

    /**
     * Get table name for static::TABLE or class name
     *
     * @example \Exemple\UserModel => exemple_user and \...\Model\CarFactory\Wheel => car_factory_wheel
     * @return string
     */
    public static function getTable(): string{
        if(static::TABLE != null)
            return static::TABLE;

        $table = static::class;
        if($prefix = strpos($table, static::CLASS_PREFIX))
            $table = substr($table, $prefix+strlen(static::CLASS_PREFIX)); //Cut class full name with CLASS_PREFIX

        if($suffix = strrpos($table, static::CLASS_SUFFIX, strrpos($table, '\\')))
            $table = substr($table, 0, $suffix); //Cut with CLASS_SUFFIX

        return strtolower(str_replace('\\', '', preg_replace('/(?<!^)[A-Z]/', '_$0', $table))); //Convert to kebab_case
    }

    /** @var array */
    public const FIELDS = [];

    /* ALL FIELD OPTIONS
        'column' => string (name in database)
        'type' => string (type in model)
        'data_type' => string (type in database)
        'length' => int (used with data_type)
        'unique' => bool|string|int|[] (define unique constraints)
        'index' => bool|string|int|[] (define indexs)
        'not_null' => bool (enable NOT NULL)
        'primary' => bool (define primary key constraint)
        'foreign' => string|[ (define foreign key)
            'constraint' => bool (create constraint in database)
            'model' => class (class to link)
            'key' => string (field to link)
            'to' => string (field linked in model)
            'multiple' => bool (allow 0-n links)
            'on_update' => string (constraint property)
            'on_delete' => string (constraint property)
        ]
        'default' => mixed (default value)
        'virtual' => bool (field not prevent in database: internal data like reverse foreign key)
        'custom' => raw sql create data
    */

    /** @var array */
    protected static $complete_fields = null;

    /**
     * Get static::FIELDS but better (used in Model exteneds)
     *
     * @return array
     */
    public static function getFields(): array{
        if(empty(static::FIELDS))
            throw new \UnexpectedValueException('static::FIELDS is empty');

        if(!isset(static::$complete_fields))
            static::$complete_fields = static::completeFields(static::FIELDS);

        return static::$complete_fields;
    }


    /*=== MAGIC ===*/
    /**
     * Construct a model element with data
     *
     * @param array $data to fill $fields
     * @param boolean $useColumns use Column's name or Field's name
     */
    public function __construct(array $data = [], bool $useColumns = false){
        foreach (static::getFields() as $field => $options) {
            $this->fields[$field] = [
                'value' => static::convertField($data[$useColumns ? static::getColumn($field, $options) : $field] ?? $options['default'] ?? null, $field, $options),
                'modified' => false
            ];
        }
    }

    /**
     * Get field value
     *
     * @param string $field value's key or foreign's key if start with _
     * @return mixed value, Model or null
     */
    public function __get(string $field){
        if(strlen($field) > 0){
            if($field[0] == '_')
               return static::loadForeign(substr($field, 1));

            if(array_key_exists($field, $this->fields) && array_key_exists('value', $this->fields[$field]))
                return $this->fields[$field]['value'];
        }

        trigger_error('Unknown property in __get() : "'.$field.'"');
        return null;
    }

    public function __isset(string $field): bool{
        if(strlen($field) > 0){
            if($field[0] == '_')
                return static::haveForeign(substr($field, 1));

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
        if(strlen($field) > 0){
            if($field[0] == '_')
                return static::saveForeign(substr($field, 1));

            if(array_key_exists($field, static::FIELDS)){
                $this->fields[$field] = [
                    'value' => static::convertField($value, $field, static::getOptions($field)),
                    'modified' => true
                ];
                return;
            }
        }
        trigger_error('Unknown property in __set() : "'.$field.'"');
    }


    /*=== QUERIES ===*/
    /**
     * Create Request\Select from Model
     *
     * @return Request\Select
     */
    public static function getSelect(): Request\Select{
        $req = Connection::get(static::getDatabase())
            ->select()->from(static::getTable());

        if(!static::WILDCARD)
            $req = $req->fields(static::getColumns(false));

        return $req;
    }

    /**
     * Really used only for next functions
     *
     * @param array $values
     * @param string $where
     * @return PDOStatement
     */
    public static function select(array $values = null, string $where = null): \PDOStatement{
        $req = static::getSelect();

        if(isset($where))
            $req = $req->where($where, true);

        return $req->run($values);
    }

    /**
     * Create Request\Insert (without data) from Model
     *
     * @return Request\Insert
     */
    public static function getInsert(): Request\Insert{
        return Connection::get(static::getDatabase())
            ->insert(static::getColumns(false))
            ->into(static::getTable());
    }

    /**
     * add values to static::getInsert and run it
     */
    public function insert(){
        $req = static::getInsert()->run($this->getColumnValues(false));
        $this->unmodify();
        return $req;
    }

    /**
     * Create Request\Update (without data) from Model
     *
     * @return Request\Update
     */
    public static function getUpdate(): Request\Update{
        return Connection::get(static::getDatabase())
            ->update()->into(static::getTable());
    }

    /**
     * Change value of modified fields in database
     *
     * @return Model
     */
    public function update(){
        static::getUpdate()
            ->fields(static::getModifiedColumns(false))
            ->where(static::getPrimarySelector())
            ->run(array_merge($this->getModifiedValues(true), $this->getPrimaryValues()));
        $this->unmodify();
        return $this;
    }

    protected static function getPrimarySelector(): string{
        return Request::combineParams(
            array_map(function($options){
                return Request::toParam($options['column']);
            }, array_filter(function($options){
                return !$options['virtual'] && $options['primary'];
            }, static::getFields()))
        );
    }

    /**
     * update or insert if primary null or modified exsits
     */
    public function save(){
        foreach(static::getFields() as $field => $options){
            if(!$options['virtual'] && $options['primary'] && ($this->fields[$field]['value'] === null || $this->fields[$field]['modified']))
                return static::insert();
        }
        //MAYBE: check exists
        return static::update();
    }

    /**
     * Create Request\Delete for modified columns (without data) from Model
     *
     * @return Request\Delete
     */
    public static function getDelete(): Request\Delete{
        return Connection::get(static::getDatabase())
            ->delete()->from(static::getTable());
    }

    /**
     * Delete instance in database
     */
    public function delete(){
        return static::getDelete()
            ->where(static::getPrimarySelector())
            ->run($this->getPrimaryValues());
    }

    /**
     * Create Request\Create from Model
     *
     * @return Request\Create
     */
    public static function getCreate(): Request\Create{
        $req = Connection::get(static::getDatabase())
            ->create(static::getTable());

        $uniques = [];
        $indexs = [];
        foreach (static::getFields() as $field => $options) {
            if(!$options['virtual']){
                $req->column($options['column'], $options['data_type'], $options['lenght'], $options['not_null'], $options['custom']);

                if($options['primary'])
                    $req->primary($column);

                foreach($options['unique'] as $unique){
                    $uniques[is_bool($unique) ? $options['column'] : $unique][] = $options['column'];
                }

                foreach($options['index'] as $index){
                    $indexs[is_bool($index) ? $options['column'] : $unique][] = $options['column'];
                }
            }
            if(static::haveForeign($field, $options) && $options['foreign']['constraint']){
                //TODO: multiple
                $req->foreign($options['foreign']['key'],
                    $options['foreign']['model']::getTable(),
                    $options['foreign']['model']::getColumn($options['foreign']['to']),
                    $options['foreign']['on_delete'],
                    $options['foreign']['on_update']);
            }
        }

        foreach($uniques as $uq_id => $columns){
            if(is_int($uq_id))
                $uq_id = implode('_', $columns);

            $req->unique($uq_id, $columns);
        }

        foreach($indexs as $id_id => $columns){
            if(is_int($id_id))
                $id_id = implode('_', $columns);

            $req->index($id_id, $columns);
        }

        return $req;
    }

    /**
     * Create Request\Drop from Model
     *
     * @return Request\Drop
     */
    public static function getDrop(): Request\Drop{
        return Connection::get(static::getDatabase())
            ->drop(static::getTable());
    }

    /**
     * Get first row than match $where with $values or null
     *
     * @param array $values
     * @param string $where
     * @param array $loads run static::load
     * @return self|null
     */
    public static function first(array $values = null, string $where = null, array $loads = []): ?self{ //MAYBE: Limit 1
        $res = static::fromRow(static::select($values, $where), false);
        if($res === null)
            return null;

        return static::load($res, $loads);
    }

    /**
     * Same as first but throw exception on null
     *
     * @param array $values
     * @param string $where
     * @param array $loads run static::load
     * @return self
     */
    public static function firstOrFail(array $values = null, string $where = null, array $loads = []): self{
        return static::load(static::fromRow(static::select($values, $where)), $loads);
    }

    /**
     * Get all rows than match $where with $values (may be empty)
     *
     * @param array $values
     * @param string $where
     * @param array $loads run static::loads
     * @return array
     */
    public static function all(array $values = null, string $where = null, array $loads = []): array{
        return static::loads(static::fromRowAll(static::select($values, $where), false), $loads);
    }

    /**
     * Same as all but throw exception on empty
     *
     * @param array $values
     * @param string $where
     * @param array $loads run static::loads
     * @return array
     */
    public static function allOrFail(array $values = null, string $where = null, array $loads = []): array{
        return static::loads(static::fromRowAll(static::select($values, $where)), $loads);
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
        $req = static::getSelect();
        $req->fields(['COUNT(*) as count']); //Hope than db simply as count()

        if(isset($where))
            $req = $req->where($where, true);

        $data = $req->run($values)->fetch();
        if(!isset($data['count']))
            return 0;

        return $data['count'];
    }

    /*=== UTILITIES ===*/
    /**
     * Get options for a specifific field
     *
     * @param string $field Field name
     * @return array Options
     */
    public static function getOptions(string $field): array{
        $fields = static::getFields();
        if(!array_key_exists($field, $fields))
            throw new \InvalidArgumentException('Can\'t find field : "'.$field.'"');
        return $fields[$field];
    }

    /**
     * Get column name from field
     *
     * @param string $field Field name
     * @return string Column name
     */
    public static function getColumn(string $field, array $options = null): string{
        return ($options ?? static::getOptions($field))['column'];
    }

    /**
     * Get column names
     *
     * @param bool $virtual
     * @return array Column names
     */
    public static function getColumns(bool $virtual = null): array{
        $columns = [];
        foreach(static::getFields() as $field => $options){
            if($virtual === null || $options['virtual'] == $virtual)
                $columns[] = $options['column'];
        }
        return $columns;
    }

    /**
     * Get modified column names
     *
     * @param bool $virtual
     * @return array Column names
     */
    public function getModifiedColumns(bool $virtual = null): array{
        $columns = [];
        foreach(static::getFields() as $field => $options){
            if(($virtual === null || $options['virtual'] == $virtual) && $this->fields[$field]['modified'])
                $columns[] = $options['column'];
        }
        return $columns;
    }

    /**
     * Get column values
     *
     * @param bool $virtual
     * @param bool $modified
     * @return array Column names
     */
    public function getColumnValues(bool $virtual = null, bool $modified = null): array{
        $values = [];
        foreach(static::getFields() as $field => $options){
            if(($virtual === null || $options['virtual'] == $virtual) && ($modified === null ||  $this->fields[$field]['modified'] == $modified))
                $values[] = $this->fields[$field]['value'];
        }
        return $values;
    }

    protected function getPrimaryValues(): array{
        $values = [];
        foreach(static::getFields() as $field => $options){
            if(!$options['virtual'] && $options['primary'])
                $values[] = $this->fields[$field]['value'];
        }
        return $values;
    }

    /**
     * Unset modified flags
     */
    public function unmodify(){
        foreach($this->fields as $field => $data){
            $this->fields[$field]['modified'] = false;
        }
    }

    /**
     * static::FIELDS[$field] with some checks
     *
     * @param string $field
     * @return array options
     */
    protected static function getFieldRawOptions(string $field): array{
        if(isset(static::FIELDS[$field]))
            return static::FIELDS[$field];

        if(!in_array($field, static::FIELDS))
            throw new \InvalidArgumentException('Unknown field : "'.$field.'"');

        return [];
    }

    /**
     * Add all missing options to FIELDS
     *
     * @param array $input FIELDS
     * @return array completed fields
     */
    protected static function completeFields(array $input): array{
        $havePrimary = false;
        $fields = [];
        foreach ($input as $field => $data) {
            if(is_int($field)){
                $field = $data;
                $data = [];
            }

            if(!is_string($field))
                throw new \UnexpectedValueException('Field name must be a string');

            if(array_key_exists($field, $fields))
                throw new \UnexpectedValueException('Duplicate field name : "'.$field.'"');

            if(!is_array($data))
                $data = ['column' => $data];

            $options = [];
            $options['column'] = $data['column'] ?? $field;
            if(!is_string($options['column']))
                throw new \UnexpectedValueException('Column name must be a string : "'.$field.'"');

            $options['virtual'] = $data['virtual'] ?? false;
            if(!is_bool($options['virtual']))
                throw new \UnexpectedValueException('Field virtual option must be a bool : "'.$field.'"');

            if(isset($data['foreign'])){
                $options['foreign']['constraint'] = $data['foreign']['constraint'] ?? $options['virtual'];
                if(!is_bool($options['foreign']['constraint']))
                    throw new \UnexpectedValueException('Field contraint of foreign must be a bool : "'.$field.'"');

                $options['foreign']['model'] = is_string($data['foreign']) ? $data['foreign'] : ($data['foreign']['model'] ?? static::class);
                if(!is_subclass_of($options['foreign']['model'], self::class))
                    throw new \UnexpectedValueException('Field model of foreign must be a subclass of "'.self::class.'" : "'.$field.'"');

                $options['foreign']['key'] = $data['foreign']['key'] ?? ($options['virtual'] ? null : $field);
                if(!is_string($options['foreign']['key']))
                    throw new \UnexpectedValueException('Field key of foreign must be a string : "'.$field.'"');

                $options['foreign']['to'] = $data['foreign']['to'] ?? call_user_func([$options['foreign']['model'], 'getID']);
                if(!is_string($options['foreign']['to']))
                    throw new \UnexpectedValueException('Field to of foreign must be a string : "'.$field.'"');

                $options['foreign']['multiple'] = $data['foreign']['multiple'] ?? false;
                if(!is_bool($options['foreign']['multiple']))
                    throw new \UnexpectedValueException('Field multiple of foreign must be a bool : "'.$field.'"');

                $options['foreign']['on_update'] = $data['foreign']['on_update'] ?? '';
                if(!is_string($options['foreign']['on_update']))
                    throw new \UnexpectedValueException('Field on_update of foreign must be a string : "'.$field.'"');

                $options['foreign']['on_delete'] = $data['foreign']['on_delete'] ?? '';
                if(!is_string($options['foreign']['on_delete']))
                    throw new \UnexpectedValueException('Field on_delete of foreign must be a string : "'.$field.'"');
            }else{
                $options['foreign'] = null;
            }

            $f_options = isset($options['foreign']['model']) ? (call_user_func([$options['foreign']['model'], 'getFieldRawOptions'], $options['foreign']['to'])) : [];

            $options['type'] = $data['type'] ?? $f_options['type'] ?? 'varchar';
            if(!is_string($options['type']))
                throw new \UnexpectedValueException('Field type must be a string : "'.$field.'"');

            if(!isset(static::DATA_TYPES[$options['type']]))
                trigger_error('Unknown data type : "'.$options['type'].'" : "'.$field.'"');

            $typeOptions = static::getDataType($options['type']);

            $options['data_type'] =  $data['data_type'] ?? $f_options['data_type'] ?? $typeOptions['data_type'] ?? $options['type'];
            if(!is_string($options['data_type']))
                throw new \UnexpectedValueException('Field data_type must be a string : "'.$field.'"');

            if($options['data_type'] != ($f_options['data_type'] ?? $options['data_type']))
                trigger_error('Data type doesn\'t match foreign model data type : "'.$field.'"');

            $options['length'] = $data['length'] ?? $f_options['length'] ?? (isset($typeOptions['length']) ? 50 : null);
            if($options['length'] != null && !is_int($options['length']))
                throw new \UnexpectedValueException('Field length must be int or null : "'.$field.'"');

            if($options['length'] != ($f_options['length'] ?? $options['length']))
                trigger_error('Data length doesn\'t match foreign model data length : "'.$field.'"');

            $options['primary'] = $data['primary'] ?? false;
            if(!is_bool($options['primary']))
                throw new \UnexpectedValueException('Field primary option must be a bool : "'.$field.'"');
            $havePrimary |= $options['primary'];

            $uniques = $data['unique'] ?? [];
            if(!is_array($uniques))
                $uniques = [$uniques];

            $options['unique'] = [];
            foreach($uniques as $unique){
                if($unique === false)
                    continue;

                if($unique !== true && !is_int($unique) && !is_string($unique))
                    throw new \UnexpectedValueException('Field unique option must be bool, int, string or array of : "'.$field.'"');
                $options['unique'][] = $unique;
            }
            $options['unique'] = array_values(array_unique($options['unique']));

            $indexs = $data['index'] ?? [];
            if(!is_array($indexs))
                $indexs = [$indexs];

            $options['index'] = [];
            $auto_index = isset($options['foreign']);
            foreach($indexs as $index){
                if($index === false){
                    $auto_index = false;
                    continue;
                }

                if($index !== true && !is_int($index) && !is_string($index))
                    throw new \UnexpectedValueException('Field index option must be bool, int, string or array of : "'.$field.'"');
                $options['index'][] = $index;
            }
            if($auto_index)
                $options['index'][] = true;
            $options['index'] = array_values(array_unique($options['index']));


            $options['not_null'] = $data['not_null'] ?? false;
            if(!is_bool($options['not_null']))
                throw new \UnexpectedValueException('Field not_null option must be a bool : "'.$field.'"');

            $options['default'] = $data['default'] ?? null;

            //TODO: increment, ...

            $options['custom'] = $data['custom'] ?? '';
            if(!is_string($options['custom']))
                throw new \UnexpectedValueException('Field custom option must be a string : "'.$field.'"');
            $fields[$field] = $options;
        }
        if(!$havePrimary)
            trigger_error('Any primary key');
        return $fields;
    }

    /**
     * Convert data using DATA_TYPES
     *
     * @param mixed $data input value
     * @param string $field field name for debug
     * @param array $options containt type and length options
     * @return mixed clean value
     */
    protected static function convertField($data, string $field, array $options){
        if(is_null($data)){
            if($options['not_null'])
                throw new \InvalidArgumentException('Can\'t set null to NOT NULL field : "'.$field.'"');
        }else{
            $typeOptions = static::getDataType($options['type']);
            $data = call_user_func($typeOptions['convert'], $data);

            if(isset($typeOptions['length']))
                if($typeOptions['length']($data) > $options['length'])
                    throw new \InvalidArgumentException('Too long data in field : "'.$field.'"');

            return $data;
        }
    }

    /**
     * Check if field is foreign
     *
     * @param string $field
     * @param array $options
     * @return boolean
     */
    public function haveForeign(string $field, array $options = null): bool{
        try{
            return isset(($options ?? static::getOptions($field))['foreign']);
        }catch(\Exception $e){
            return false;
        }
    }

    public function isLoadedForeign(string $field): bool{
        return array_key_exists($field, $this->fields) && array_key_exists('foreign', $this->fields[$field]);
    }

    /**
     * Autoload foreign model
     *
     * @param string $field
     * @param bool $reload
     * @return null|Model|array
     */
    public function loadForeign(string $field, bool $reload = false){
        if(!$reload && $this->isLoadedForeign($field))
            return $this->fields[$field]['foreign'];

        $options = static::getOptions($field);
        if(!static::haveForeign($field, $options))
            throw new \InvalidArgumentException('Field is not a foreign key : "'.$field.'"');

        $model = $options['foreign']['model'];
        $key = $options['foreign']['key'];

        $where = Request::toParam($model::getColumn($options['foreign']['to']));
        $value = $options['foreign']['multiple'] ?
            $model::all([$this->{$key}], $where):
            $model::first([$this->{$key}], $where);

        //MAYBE: Make nullable check

        $this->saveForeign($field, $value, false, $options);
        return $value;
    }

    /**
     * Autoload foreign model for multiple models
     *
     * @param array $models
     * @param string $field
     * @param bool $reload
     * @return array values
     */
    public static function loadsForeign(array $models, string $field, bool $reload = false){ //TODO: multiload
        if(!empty($models)){
            $options = static::getOptions($field);
            if(!static::haveForeign($field, $options))
                throw new \InvalidArgumentException('Field is not a foreign key : "'.$field.'"');

            $model = $options['foreign']['model'];
            $key = $options['foreign']['key'];
            $to = $options['foreign']['to'];
            $multiple = $options['foreign']['multiple'];

            $values = [];
            if(!$reload){
                foreach($models as $model){
                    $id = $model->{$key};
                    if($model->isLoadedForeign($field) && !array_key_exists($id, $values))
                        $values[$id] = $model->loadForeign($field);
                }
                $toload = [];
                foreach($models as $model){
                    if(!$model->isLoadedForeign($field)){
                        $id =$model->{$key};
                        if(array_key_exists($id, $values))
                            $model->saveForeign($field, $values[$id], false, $options);
                        else
                            $toload[] = $model;
                    }
                }
                $models = $toload;
            }

            $ids = [];
            foreach ($models as $current) {
                $ids[] = $current->{$key};
            }
            $ids = array_values(array_unique($ids));

            foreach($model::all($ids, Request::inParams($model::getColumn($to), $ids)) as $current){
                $cid = $current->{$to};
                if($multiple)
                    $values[$cid][] = $current;
                else
                    $values[$cid] = $current;
            }

            //MAYBE: Make nullable check
            foreach($models as $model){
                if($reload || !$model->isLoadedForeign($field))
                    $model->saveForeign($field, $values[$model->{$key}] ?? null, false, $options);
            }

            $res = [];
            foreach($values as $key => $value){
                array_push($res, $value);
            }
            return array_values(array_unique($res));
        }
    }

    /**
     * Recursivly autoload foreign models
     *
     * @param self $model
     * @param array $loads
     * @return Model update model
     */
    public static function load(self $model, array $loads): self{
        foreach($loads as $field => $data){
            $subfields = [];
            if(is_array($data)){
                $subfields = $data;
            }else{
                $field = $data;
            }
            $loaded = $model->loadForeign($field);
            if(!empty($subfields) && $loaded !== null){
                if(is_array($loaded)){
                    $loaded[0]::loads($loaded, $subfields);
                }else{
                    $loaded::load($loaded, $subfields);
                }
            }
        }
        return $model;
    }

    /**
     * Recursivly autoload foreign models for multiple models
     *
     * @param array $models
     * @param array $loads
     * @return array updated models
     */
    public static function loads(array $models, array $loads): array{
        foreach($loads as $field => $data){
            $subfields = [];
            if(is_array($data)){
                $subfields = $data;
            }else{
                $field = $data;
            }
            $loaded = static::loadsForeign($models, $field);
            if(!empty($subfields))
                $loaded[0]::loads($loaded, $subfields);
        }
        return $models;
    }

    public function saveForeign(string $field, $data, bool $updateKey = false, array $options = null){
        if(!is_a($data, static::class) && !is_array($data) && $data !== null)
            throw new \InvalidArgumentException('Set data must be a Model, array of Model or null');

        $options = $options ?: static::getOptions($field);
        if(!static::haveForeign($field, $options))
            throw new \InvalidArgumentException('Field is not a foreign key : "'.$field.'"');

        if($options['foreign']['multiple'] && !is_array($data)){
            if($data === null)
                $data = [];
            else
                $data = [$data];
        }

        $this->fields[$field]['foreign'] = $data;

        if($updateKey){
            if($data === null)
                $this->{$field} = null;
            elseif(is_a($data, static::class))
                $this->{$field} = $data->{$options['foreign']['to']};
        }
    }

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
                throw new \InvalidArgumentException('Create from Any Row');
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
                throw new \InvalidArgumentException('Create from Any Row');
            return [];
        }

        $res = [];
        while($data = $row->fetch()){
            $res[] = new static($data, true);
        }
        return $res;
    }

    /**
     * Get fields values (reverse of static::__construct)
     *
     * @return array
     */
    public function getValues(): array{
        return array_map(function($data){ return $data['value']; }, $this->fields);
    }
}