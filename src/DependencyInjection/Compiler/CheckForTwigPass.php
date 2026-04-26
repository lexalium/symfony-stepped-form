<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\DependencyInjection\Compiler;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CheckForTwigPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasParameter('stepped_form.twig_needed') && !$container->has('twig')) {
            throw new LogicException(
                'Stepped Form requires "twig" to be available. Make sure that twig-bundle' .
                ' correctly installed and configured or use your own implementation of renderer.'
            );
        }

        $container->getParameterBag()->remove('stepped_form.twig_needed');
    }
}
