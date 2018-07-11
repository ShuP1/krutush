$migration->create(
    Krutush\Database\Exemple\Model\UserModel:class,
    Krutush\Database\ModelID:class,
    [
        'id' => [
            'column' => 'user_id',
            'type' => 'bigint'
        ],
        'first_name' => [
            'not_null' => true
        ],
        'last_name' => [
            'not_null' => true
        ],
        'supervisor' => [
            'foreign' => []
        ],
        'supervised' => [
            'virtual' => true,
            'foreign' => [
                'key' => 'supervisor',
                'multiple' => true
            ]
        ]
    ]);