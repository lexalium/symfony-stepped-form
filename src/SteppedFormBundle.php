<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm;

use Lexal\SymfonySteppedForm\DependencyInjection\Compiler\CheckForEventDispatcherPass;
use Lexal\SymfonySteppedForm\DependencyInjection\Compiler\CheckForTwigPass;
use Lexal\SymfonySteppedForm\DependencyInjection\Compiler\ExceptionNormalizerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function dirname;

final class SteppedFormBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ExceptionNormalizerPass());
        $container->addCompilerPass(new CheckForEventDispatcherPass());
        $container->addCompilerPass(new CheckForTwigPass());
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
