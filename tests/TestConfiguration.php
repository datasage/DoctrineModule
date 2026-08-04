<?php

declare(strict_types=1);

return [
    'modules' => [
        'Laminas\Form',
        'Laminas\Paginator',
        'Laminas\Validator',
        'DoctrineModule',
    ],
    'module_listener_options' => [
        'config_glob_paths' => [],
        'module_paths' => [],
        // Modules are autoloaded by Composer, so laminas-loader is not needed.
        'use_laminas_loader' => false,
    ],
];
