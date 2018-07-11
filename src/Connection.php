<?php

namespace Krutush\Database;

/**
 * Load a config file to provide a list of pdo instances (Database)
 */
class Connection{
    /** @var array */
    protected static $databases = array();

    /** @var array */
    protected $settings;

    /** Load settings */
    public function __construct(string $path = null){
        if(isset($path))
            $this->settings = include($path);
    }

    /** Setup Database */
    public function connect(string $dbname = null): Database{
        if(static::exists($dbname))
            throw new \InvalidArgumentException("Allready connect");

        $dbname = static::parseName($dbname);
        if(!isset($this->settings[$dbname]))
            throw new \InvalidArgumentException('Can\'t find '.$dbname.' in settings');

        static::$databases[$dbname] = new Database($this->settings[$dbname]);
        return static::$databases[$dbname];
    }

    /** If you dont remember how to make a try-catch */
    public function tryConnect(string $dbname = null, bool $quiet = false) {
        try {
            return $this->connect($dbname);
        } catch (\Exception $e) {
            return $quiet ? false : $e;
        }
    }

    public static function get(string $dbname = null){
        $dbname = static::parseName($dbname);
        if(!static::exists($dbname))
            throw new \InvalidArgumentException('Can\'t find "'.$dbname.'"');

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
        } catch (\Exception $e) {
            return $quiet ? false : $e;
        }
    }

    public function tryGetCreate(string $dbname = null, bool $quiet = false) {
        try {
            return $this->getCreate($dbname);
        } catch (\Exception $e) {
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