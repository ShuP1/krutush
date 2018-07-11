$migration->alter(
    Krutush\Database\Exemple\Model\UserModel:class,
    [
        'first_name' => [
            'unique' => 'full_name'
        ],
        'last_name' => [
            'unique' => 'full_name'
        ]
    ]);