<?php

use App\Kernel;

// Aligner le timezone PHP avec MySQL — résout DoctrineDoctor "Timezone mismatch"
date_default_timezone_set('Europe/Berlin');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $env = $context['APP_ENV'] ?? 'prod';
    return new Kernel(is_string($env) ? $env : 'prod', (bool) $context['APP_DEBUG']);
};
