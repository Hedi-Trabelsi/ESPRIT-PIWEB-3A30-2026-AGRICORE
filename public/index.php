<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $env = $context['APP_ENV'] ?? 'prod';
    return new Kernel(is_string($env) ? $env : 'prod', (bool) $context['APP_DEBUG']);
};
