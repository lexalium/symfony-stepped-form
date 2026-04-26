<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

final class ExceptionNormalizerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('stepped_form.exception_normalizer')) {
            return;
        }

        $exceptionNormalizers = $this->findAndSortTaggedServices('stepped_form.exception_normalizer', $container);

        if ($exceptionNormalizers === []) {
            throw new RuntimeException(
                'You must tag at least one service as "stepped_form.exception_normalizer"' .
                ' to use the "stepped_form.exception_normalizer" service.'
            );
        }

        $exceptionNormalizerDefinition = $container->getDefinition('stepped_form.exception_normalizer');
        $exceptionNormalizerDefinition->replaceArgument(0, $exceptionNormalizers);
    }
}
