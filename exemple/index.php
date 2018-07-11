<?php
require_once('../vendor/autoload.php');

(new Krutush\Database\Connection('Databases.php'))->connect();

$UserModel = Krutush\Database\Exemple\Model\UserModel::class;

var_dump($UserModel::getTable());
//var_dump($UserModel::getFields());

var_dump((new $UserModel([
    'id' => 42,
    'first_name' => 'Pierre De La VIEDDDDDDD',
    'last_name' => 'Caillou'
]))->insert());

//TODO: format src before commit