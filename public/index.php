#!/usr/bin/env php
<?php

use App\Application;

include __DIR__ . '/../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    printf('This application can be launched in terminal only!%s', PHP_EOL);
    die();
}

if (extension_loaded('ext-redis')) {
    printf('Extension ext-redis is not found!%s', PHP_EOL);
    die();
}

$options = [
    Application::SRC_ARG_KEY => '',
    Application::SRC_REDIS_PASS_ARG_KEY => '',
    Application::SRC_REDIS_PORT_ARG_KEY => '',
    Application::DST_ARG_KEY => '',
    Application::DST_REDIS_PASS_ARG_KEY => '',
    Application::DST_REDIS_PORT_ARG_KEY => '',
    Application::KEY_DELIMITER_ARG_KEY => '',
];

array_shift($argv);
foreach ($argv as $i => $key) {
    if (
        isset($options[$key]) &&
        isset($argv[$i + 1]) &&
        !isset($options[$argv[$i + 1]])
    ) {
        $options[$key] = $argv[$i + 1];
    }

    if ($key === Application::FLUSH_DST_ARG_KEY) {
        $options[Application::FLUSH_DST_ARG_KEY] = true;
    }
}

try {
    $application = new Application($options);
    if ($application->isValid()) {
        $application->start();
    }
} catch (\Exception $e) {
    echo sprintf('%s%s', $e->getMessage(), PHP_EOL);
}
