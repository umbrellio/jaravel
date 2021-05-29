<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__ . '/vendor/umbrellio/code-style-php/umbrellio-cs.php');

    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::CACHE_DIRECTORY, '.ecs_cache');
    $parameters->set(Option::FILE_EXTENSIONS, ['php']);

    $parameters->set(Option::PATHS, [__DIR__ . '/src', __DIR__ . '/tests']);
};
