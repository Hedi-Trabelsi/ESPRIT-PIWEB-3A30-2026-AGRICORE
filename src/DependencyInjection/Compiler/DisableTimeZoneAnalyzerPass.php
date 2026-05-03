<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use AhmedBhs\DoctrineDoctor\Analyzer\Configuration\TimeZoneAnalyzer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DisableTimeZoneAnalyzerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(TimeZoneAnalyzer::class)) {
            $container->removeDefinition(TimeZoneAnalyzer::class);
        }
    }
}
