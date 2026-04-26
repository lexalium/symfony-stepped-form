<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\DependencyInjection\Compiler;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function sprintf;

final class CheckForEventDispatcherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            $container->hasParameter('stepped_form.event_dispatcher_needed')
            && !$container->has(EventDispatcherInterface::class)
        ) {
            throw new LogicException(
                sprintf(
                    'Stepped Form requires "%s" implementation. Make sure that symfony/event-dispatcher' .
                    ' correctly installed and configured or use your own implementation of event dispatcher.',
                    EventDispatcherInterface::class,
                ),
            );
        }

        $container->getParameterBag()->remove('stepped_form.event_dispatcher_needed');
    }
}
