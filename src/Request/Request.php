<?php

namespace Krutush\Database\Request;

use Krutush\Database\Database;

/** Base of any SQL request */
class Request{
    /** @var Database */
    protected $db;

    /** Create it */
    public function __construct(Database $db){
        $this->db = $db;
    }

    /** Run it */
    protected function execute(string $sql, array $values = null){
        return $this->db->execute($sql, $values, true);
    }

    /*=== TOOLS ===*/
    public static function toParam(string $name, string $operator = '='): string{
        return $name.' '.$operator.' ?';
    }

    public static function toParams(array $names): string{
        return implode(', ', array_map(function($name){ return static::toParam($name); }, $names));
    }

    public static function paramList(int $count): string{
        return implode(',', array_fill(0, $count, '?'));
    }

    public static function inParams(string $name, $params): string{
        return $name.' IN ('.static::paramList(is_int($params) ? $params : count($params)).')';
    }

    public static function combineParams(array $params, string $operator = ' AND '): string{
        return implode($operator, $params);
    }
}