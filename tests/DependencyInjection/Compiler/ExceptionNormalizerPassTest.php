<?php

declare(strict_types=1);

namespace DependencyInjection\Compiler;

use Lexal\SymfonySteppedForm\DependencyInjection\Compiler\ExceptionNormalizerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

final class ExceptionNormalizerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ExceptionNormalizerPass());
    }

    public function testAddNormalizersAsAnArgument(): void
    {
        $exceptionNormalizer = $this->registerService('custom.exception_normalizer1', 'custom.exception_normalizer1');
        $exceptionNormalizer->addTag('stepped_form.exception_normalizer', ['priority' => 100]);

        $definition = $this->registerService('stepped_form.exception_normalizer', 'exception_normalizer');
        $definition->setArgument(0, new AbstractArgument('list of exception normalizers'));

        $this->compile();

        self::assertEquals([new Reference('custom.exception_normalizer1')], $definition->getArgument(0));
    }

    public function testThrowsExceptionWhenNoNormalizersDeclared(): void
    {
        $this->expectExceptionObject(
            new RuntimeException(
                'You must tag at least one service as "stepped_form.exception_normalizer"' .
                ' to use the "stepped_form.exception_normalizer" service.'
            ),
        );

        $this->registerService('stepped_form.exception_normalizer', 'exception_normalizer');

        $this->compile();
    }
}
