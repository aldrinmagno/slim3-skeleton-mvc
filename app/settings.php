<?php
return [
    'settings' => [
        // comment this line when deploy to production environment
        'displayErrorDetails' => getenv('debug'),
        'debug'               => getenv('debug'),
        // View settings
        'view' => [
            'template_path' => __DIR__ . '/templates',
            'twig' => [
            //    'cache' => __DIR__ . getenv('cache'),
                'debug' => getenv('debug'),
                'auto_reload' => getenv('auto_reload'),
            ],
        ],

        'upload' => [
          'path' => getenv('upload'),
        ],

        // PDO settings
        'pdo' => [
          'host'     => getenv('host'),
          'port'     => getenv('port'),
          'dbname'   => getenv('dbname'),
          'user'     => getenv('user'),
          'password' => getenv('password'),
        ],

        // monolog settings
        'logger' => [
            'name' => 'app',
            'path' => __DIR__ . '/../log/app.log',
        ],
    ],
];
