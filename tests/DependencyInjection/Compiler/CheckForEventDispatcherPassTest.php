<?php

declare(strict_types=1);

namespace DependencyInjection\Compiler;

use Lexal\SymfonySteppedForm\DependencyInjection\Compiler\CheckForEventDispatcherPass;
use LogicException;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function sprintf;

final class CheckForEventDispatcherPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CheckForEventDispatcherPass());
    }

    public function testThrowsExceptionWhenEventDispatcherIsRequired(): void
    {
        $this->container->setParameter('stepped_form.event_dispatcher_needed', true);

        $this->expectExceptionObject(
            new LogicException(
                sprintf(
                    'Stepped Form requires "%s" implementation. Make sure that symfony/event-dispatcher' .
                    ' correctly installed and configured or use your own implementation of event dispatcher.',
                    EventDispatcherInterface::class,
                ),
            ),
        );

        $this->compile();
    }

    public function testWhenHasParameterAndEventDispatcherIsRegistered(): void
    {
        $this->container->setParameter('stepped_form.event_dispatcher_needed', true);
        $this->container->register(EventDispatcherInterface::class, 'event_dispatcher');

        $this->compile();

        self::assertFalse($this->container->hasParameter('stepped_form.event_dispatcher_needed'));
    }

    public function testWhenDoesNotHaveParameter(): void
    {
        $this->compile();

        self::assertFalse($this->container->hasParameter('stepped_form.event_dispatcher_needed'));
    }
}
