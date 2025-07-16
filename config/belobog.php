<?php

return [
    'migrations' => [
        'dir' => base_path() . '/database/migrations',
        'namespace' => 'Database\Migrations',
    ],

    'seeders' => [
        'dir' => base_path() . '/database/seeds',
        'namespace' => 'Database\Seeds'
    ],

    'scripts' => [
        'dir' => base_path() . '/database/scripts',
        'namespace' => 'Database\Scripts'
    ],

    'models' => [
        'dir' => base_path() . '/database/models',
        'namespace' => 'Database\Models'
    ],

];