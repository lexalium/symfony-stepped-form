<?php

declare(strict_types=1);

namespace DependencyInjection\Compiler;

use Lexal\SymfonySteppedForm\DependencyInjection\Compiler\CheckForTwigPass;
use LogicException;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CheckForTwigPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CheckForTwigPass());
    }

    public function testThrowsExceptionWhenTwigBundleIsRequired(): void
    {
        $this->container->setParameter('stepped_form.twig_needed', true);

        $this->expectExceptionObject(
            new LogicException(
                'Stepped Form requires "twig" to be available. Make sure that twig-bundle' .
                ' correctly installed and configured or use your own implementation of renderer.'
            ),
        );

        $this->compile();
    }

    public function testWhenHasParameterAndTwigBundleIsRegistered(): void
    {
        $this->container->setParameter('stepped_form.twig_needed', true);
        $this->container->register('twig', 'custom.twig');

        $this->compile();

        self::assertFalse($this->container->hasParameter('stepped_form.twig_needed'));
    }

    public function testWhenDoesNotHaveParameter(): void
    {
        $this->compile();

        self::assertFalse($this->container->hasParameter('stepped_form.twig_needed'));
    }
}
