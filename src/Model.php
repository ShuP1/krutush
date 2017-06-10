<?php

namespace Krutush\Database;

use Inutils\Storage\Container;

//TODO extends  
//TODO protected to const php7
//TODO add model links

class Model extends Container{
    protected static $DATABASE = null;
    protected static $TABLE = null;
    protected static $FIELDS = null;
    protected static $ID = null;
    protected static $REF = null;
    protected static $FILTER = null;
    protected static $INNER = null;
    protected static $ORDER = null;

    protected $modify = false;

    public function __set($key, $value){
        //TODO Check format
        $this->modify = true;
        parent::__set($key, $value);
    }

    /*=== CREATE ===*/

    public static function fromRow($row, $all = true, $exception = true){
        if($row->rowCount() < 1){
            if($exception)
                throw new \Exception('Create from Any Row');
            return;
        }

        if($all){
            $res = array();
            while($data = $row->fetch()){
                $res[] = new static($data);
            }
            return $res;
        }else{
            $data = $row->fetch();
            return new static($data);
        }
    }

    public static function fromData($data){
        $res = array();
        foreach($data as $element){
            $res[] = new static($element);
        }
        return $res;
    }

    /*=== CONST ===*/

    public static function getFields($exception = false){
        if(!isset(static::$FIELDS)){
            if($exception)
                throw new \Exception('FIELDS not set');
            return;
        }

        return static::$FIELDS;
    }

    public static function getField($alias){
        $fields = static::getFields();
        if(!isset($fields[$alias]))
            throw new \Exception('Can\'t find alias : '.$alias);

        return $fields[$alias];
    }

    public static function getID($real = true){
        if(static::$ID == null){
            if($real)
                return static::getField('ID');

            throw new \Exception('ID not set');
        }

        return $real == true ? static::getField(static::$ID) : static::$ID;
    }

    public static function getREF($real = true){
        if(static::$REF == null){
            if($real)
                return static::getField('REF');

            throw new \Exception('REF not set');
        }

        return $real == true ? static::getField(static::$REF) : static::$REF;
    }

    public static function getDATABASE(){
        return isset(static::$DATABASE) ? static::$DATABASE : null;
    }

    /*=== QUERIES ===*/

    public static function prepare(){
        $req = Connection::get(static::getDATABASE())
            ->select(static::getFields())
            ->from(static::$TABLE);

        if(isset(static::$INNER))
            $req = $req->join(static::$INNER);

        if(isset(static::$FILTER))
            $req = $req->where(static::$FILTER);

        if(isset(static::$ORDER))
            $req = $req->orderby(static::$ORDER);

        return static::postPrepare($req);
    }

    public static function postPrepare($req){ return $req; }

    public static function row(array $values = null, $filters = null){
        $req = static::prepare();

        if(isset($filters))
            $req = $req->where($filters, true);

        return $req->run($values);
    }

    public static function find($id) {
         return static::first(array($id), (static::getID().' = ?'));
    }

    public static function findOrFail($id) {
         return static::firstOrFail(array($id), (static::getID().' = ?'));
    }

    public static function first(array $values = null, $filters = null) {
        return static::fromRow(static::row($values, $filters), false, false);
    }

    public static function firstOrFail(array $values = null, $filters = null) {
        return static::fromRow(static::row($values, $filters), false);
    }

    public static function all(array $values = null, $filters = null) {
        return static::fromRow(static::row($values, $filters), true, false);
    }

    public static function allOrFail(array $values = null, $filters = null) {
        return static::fromRow(static::row($values, $filters));
    }

    public static function byRef($ref){
        return static::first(array($ref), (static::getREF().' = ?'));
    }

    public static function byRefOrFail($ref){
        return static::firstOrFail(array($ref), (static::getREF().' = ?'));
    }

    public static function resolveID($ref, $exception = false) {
        $res = static::fromRow(static::row(array($ref), (static::getREF().' = ?')), false, $exception);
        if(!isset($res))
            return;

        $id = static::getID(false);
        return $res->$id;
    }

    public static function exists(array $values = null, $filters = null){
        return static::first($values, $filters) !== null;
    }

    //TODO clean
    public static function count(array $values = null, $filters = null){
        $req = static::prepare();
        $req = $req->fields(array('COUNT(*)'));

        if(isset($filters))
            $req = $req->where($filters, true);

        $data = $req->run($values)->fetch();
        if(!isset($data['COUNT(*)']))
            return;

        return $data['COUNT(*)'];
    }
}