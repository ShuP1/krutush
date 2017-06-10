<?php

namespace MVC\Database\Request;

use MVC\Database\Database;

class Request{
    protected $db;

    public function __construct(Database $db){
        $this->db = $db;
    }

    protected function execute(string $sql, array $values = null){
        return $this->db->execute($sql, $values, true);
    }
}