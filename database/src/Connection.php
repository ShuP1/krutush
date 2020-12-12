<?php

namespace Krutush\Database;

class Connection{
    protected static $databases = array();

    protected $settings;

    public function __construct(string $path = null){
        if(isset($path))
            $this->settings = include($path);
    }

    public function connect(string $dbname = null){
        if(static::exists($dbname))
            throw new DatabaseException("Allready connect");

        $dbname = static::parseName($dbname);
        if(!isset($this->settings[$dbname]))
            throw new DatabaseException('Can\'t find '.$dbname.' in settings');

        static::$databases[$dbname] = new Database($this->settings[$dbname]);
        return static::$databases[$dbname];
    }

    public function tryConnect(string $dbname = null, bool $quiet = false) {
        try {
            return $this->connect($dbname);
        } catch (DatabaseException $e) {
            return $quiet ? false : $e;
        }
    }

    public static function get(string $dbname = null){
        $dbname = static::parseName($dbname);
        if(!static::exists($dbname))
            throw new DatabaseException('Can\'t find "'.$dbname.'"');

        return static::$databases[$dbname];
    }

    public function getCreate(string $dbname = null){
        if(!static::exists($dbname)){
            $this->create($dbname);
        }
        return static::$databases[static::parseName($dbname)];
    }

    public static function tryGet(string $dbname = null, bool $quiet = false) {
        try {
            return static::get($dbname);
        } catch (DatabaseException $e) {
            return $quiet ? false : $e;
        }
    }

    public function tryGetCreate(string $dbname = null, bool $quiet = false) {
        try {
            return $this->getCreate($dbname);
        } catch (DatabaseException $e) {
            return $quiet ? false : $e;
        }
    }

    public static function exists(string $dbname = null){
        $dbname = static::parseName($dbname);
        return isset(static::$databases[$dbname]);
    }

    private static function parseName(string $dbname = null){
        return $dbname ?: 'default'; //Edit me
    }
}