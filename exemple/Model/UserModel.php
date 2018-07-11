<?php
namespace Krutush\Database\Exemple\Model;

use Krutush\Database\ModelID;

class UserModel extends ModelID{
    public const FIELDS = [
        'id' => [
            'column' => 'user_id',
            'type' => 'bigint'
        ],
        'first_name' => [
            'not_null' => true,
            'unique' => 'full_name'
        ],
        'last_name' => [
            'not_null' => true,
            'unique' => 'full_name'
        ],
        'supervisor' => [
            'foreign' => []
        ],
        'supervised' => [
            'virtual' => true,
            'index' => true,
            'foreign' => [
                'key' => 'supervisor',
                'multiple' => true
            ]
        ],
        'office' => [
            'foreign' => OfficeModel::class,
        ]
    ];
}