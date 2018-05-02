<?php

namespace Krutush\Database;

class Database{
    private $pdo;

    public function __construct(array $settings){
        $dns = $settings['driver'] .
        ':host=' . $settings['host'] .
        ((isset($settings['port'])) ? (';port=' . $settings['port']) : '') .
        ';dbname=' . $settings['schema'] .
        ((isset($settings['charset'])) ? (';charset=' . $settings['charset']) : '');

        $this->pdo = new \PDO($dns, $settings['username'], $settings['password'], $settings['options']);
    }

    public function exec(string $request){
        return $this->pdo->exec($request);
    }

    public function prepare(string $request){
        return $this->pdo->prepare($request);
    }

    public function execute(string $request, array $values = null, $row = false){
        $req = $this->prepare($request);
        $req->execute($values);
        if($row == false)
            return $req->fetchAll();

        return $req;
    }

    public function select(array $fields = null){
        $select = new Request\Select($this);
        if(isset($fields))
            return $select->fields($fields);

        return $select;
    }

    public function insert(array $fields = null){
        $insert = new Request\Insert($this);
        if(isset($fields))
            return $insert->fields($fields);

        return $insert;
    }

    public function update(array $fields = null){
        $update = new Request\Update($this);
        if(isset($fields))
            return $update->fields($fields);

        return $update;
    }

    public function create(string $table = null){
        $create = new Request\Create($this);
        if(isset($table))
            return $create->table($table);

        return $create;
    }

    public function drop(string $table = null){
        $drop = new Request\Drop($this);
        if(isset($table))
            return $drop->table($table);

        return $drop;
    }

    public function getLastInsertId(){
        return $this->pdo->lastInsertId();
    }
    //TODO update, delete
}