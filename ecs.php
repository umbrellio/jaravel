<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__ . '/vendor/umbrellio/code-style-php/umbrellio-cs.php');

    $services = $containerConfigurator->services();

    $services->set(PhpUnitTestAnnotationFixer::class)
        ->call('configure', [[
            'style' => 'prefix',
        ]]);

    $services->set(DeclareStrictTypesFixer::class);
    $services->set(NoUnusedImportsFixer::class);
    $services->set(NoEmptyPhpdocFixer::class);

    $services->set(BlankLineBeforeStatementFixer::class)
        ->call('configure', [[
            'statements' => ['if', 'return'],
        ]]);

    $services->set(PhpdocLineSpanFixer::class)
        ->call('configure', [[
            'method' => 'single',
            'property' => 'single',
            'const' => 'single',
        ]]);

    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::CACHE_DIRECTORY, '.ecs_cache');
    $parameters->set(Option::FILE_EXTENSIONS, ['php']);

    $parameters->set(Option::PATHS, [__DIR__ . '/src', __DIR__ . '/tests']);
};
