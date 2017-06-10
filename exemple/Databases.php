<?php return array(
    'default' => array(
        'driver' => 'mysql',
        'host' => 'localhost',
    //  'port' => '3306',
        'schema' => 'mydatabase',
        'charset' => 'uft8',
        'username' => 'webuser',
        'password' => 'xxxxxxxxx',
        'options' => array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //Exceptions pour les erreurs sql
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8', //TODO deprecated remove on recent server
            PDO::ATTR_PERSISTENT => true, //Connection persistente
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC //Pas d'index
        )
    ),
    'admin' => array(
        'driver' => 'mysql',
        'host' => 'localhost',
    //  'port' => '3306',
        'schema' => 'mydatabase',
        'charset' => 'uft8',
        'username' => 'admin',
        'password' => 'xxxxxxxxx',
        'options' => array()
    )
);