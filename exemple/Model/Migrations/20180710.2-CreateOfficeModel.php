$migration->create(
    Krutush\Database\Exemple\Model\OfficeModel:class,
    Krutush\Database\ModelID:class,
    ['name']
)->alter(
    Krutush\Database\Exemple\Model\UserModel:class,
    [
        'office' => [
            'foreign' => Krutush\Database\Exemple\Model\OfficeModel:class,
        ]
    ]);