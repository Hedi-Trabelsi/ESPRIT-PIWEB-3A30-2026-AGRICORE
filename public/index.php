<?php

use App\Kernel;

// Aligner le timezone PHP avec MySQL (+01:00) — résout DoctrineDoctor "Timezone mismatch".
// Africa/Tunis = UTC+1 toute l'année (pas de DST), donc correspond exactement au "+01:00"
// rapporté par MySQL. Europe/Berlin causait un décalage d'1h en été (DST → UTC+2).
date_default_timezone_set('Africa/Tunis');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $env = $context['APP_ENV'] ?? 'prod';
    return new Kernel(is_string($env) ? $env : 'prod', (bool) $context['APP_DEBUG']);
};
