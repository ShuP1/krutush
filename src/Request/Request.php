<?php

namespace Krutush\Database\Request;

use Krutush\Database\Database;

class Request{ //TODO: escape and slugify
    protected $db;

    public function __construct(Database $db){
        $this->db = $db;
    }

    protected function execute(string $sql, array $values = null){
        return $this->db->execute($sql, $values, true);
    }
}