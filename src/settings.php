<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'view' => [
            'template_path' => __DIR__ . '/../templates',
            'twig' => [
                'cache' => __DIR__ . '/../.cache',
                'debug' => true,
                'auto_reload' => true,
            ]
        ],
        // Monolog settings
        'logger' => [
            'name' => 'app',
            'path' => !empty(getenv('DOCKER')) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        // DB connection
        'db' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST', true) ? getenv('DB_HOST') : '127.0.0.1',
            'database' => getenv('DB_NAME', true) ? getenv('DB_NAME') : '',
            'username' => getenv('DB_USER', true) ? getenv('DB_USER') : '',
            'password' => getenv('DB_PASS', true) ? getenv('DB_PASS') : '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]
    ],
];
