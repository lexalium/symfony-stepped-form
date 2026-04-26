<?php

declare(strict_types=1);

namespace DependencyInjection;

use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizerInterface;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\AlreadyStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\DefaultExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\EntityNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\FormNotStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotRenderableExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotSubmittedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\SteppedFormErrorsExceptionNormalizer;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\HttpSteppedForm\SteppedForm as HttpSteppedForm;
use Lexal\HttpSteppedForm\SteppedFormInterface;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Lexal\SteppedForm\Form\DataControl;
use Lexal\SteppedForm\Form\StepControl;
use Lexal\SteppedForm\Form\Storage\DataStorage;
use Lexal\SteppedForm\Form\Storage\FormStorage;
use Lexal\SteppedForm\SteppedForm;
use Lexal\SymfonySteppedForm\DependencyInjection\Configuration;
use Lexal\SymfonySteppedForm\DependencyInjection\Factory\SteppedFormFactory;
use Lexal\SymfonySteppedForm\DependencyInjection\SteppedFormExtension;
use Lexal\SymfonySteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\SymfonySteppedForm\Renderer\Renderer;
use Lexal\SymfonySteppedForm\Routing\Redirector;
use Lexal\SymfonySteppedForm\Tests\CustomStep;
use Lexal\SymfonySteppedForm\Tests\FormBuilder;
use Lexal\SymfonySteppedForm\Tests\FormSettings;
use Lexal\SymfonySteppedForm\Tests\SessionKeyStorage;
use Lexal\SymfonySteppedForm\Tests\Storage;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class SteppedFormExtensionTest extends AbstractExtensionTestCase
{
    private const NORMALIZERS_WITH_CLASS_AND_PRIORITY = [
        'stepped_form.exception_normalizer.already_started' => [AlreadyStartedExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.step_not_found' => [StepNotFoundExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.step_not_renderable' => [StepNotRenderableExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.step_not_submitted' => [StepNotSubmittedExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.entity_not_found' => [EntityNotFoundExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.form_not_started' => [FormNotStartedExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.stepped_form_errors' => [SteppedFormErrorsExceptionNormalizer::class, -100],
        'stepped_form.exception_normalizer.default' => [DefaultExceptionNormalizer::class, -200],
    ];

    private const NORMALIZERS_WITH_ARGUMENTS = [
        'stepped_form.exception_normalizer.already_started' => ['stepped_form.redirector'],
        'stepped_form.exception_normalizer.step_not_submitted' => ['stepped_form.redirector'],
        'stepped_form.exception_normalizer.entity_not_found' => ['stepped_form.redirector'],
        'stepped_form.exception_normalizer.form_not_started' => ['stepped_form.redirector'],
        'stepped_form.exception_normalizer.stepped_form_errors' => ['stepped_form.redirector'],
    ];

    protected function getContainerExtensions(): array
    {
        return [new SteppedFormExtension()];
    }

    public function testRegistersDefaultAliases(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(RendererInterface::class, Renderer::class);
        $this->assertContainerBuilderHasAlias(RedirectorInterface::class, Redirector::class);
        $this->assertContainerBuilderHasAlias(EventDispatcherInterface::class, EventDispatcher::class);

        $this->assertContainerBuilderHasExactParameter('stepped_form.event_dispatcher_needed', true);
        $this->assertContainerBuilderHasExactParameter('stepped_form.twig_needed', true);

        $this->assertContainerBuilderHasService('stepped_form.factory', SteppedFormFactory::class);

        foreach (self::NORMALIZERS_WITH_CLASS_AND_PRIORITY as $id => [$class, $priority]) {
            $this->assertContainerBuilderHasService($id, $class);
            $this->assertContainerBuilderHasServiceDefinitionWithTag(
                $id,
                'stepped_form.exception_normalizer',
                ['priority' => $priority],
            );
        }

        $this->assertContainerBuilderHasService('stepped_form.exception_normalizer', ExceptionNormalizer::class);
        $this->assertContainerBuilderHasAlias(ExceptionNormalizerInterface::class, 'stepped_form.exception_normalizer');

        foreach (self::NORMALIZERS_WITH_ARGUMENTS as $id => $arguments) {
            foreach ($arguments as $index => $argument) {
                $this->assertContainerBuilderHasServiceDefinitionWithArgument($id, $index, new Reference($argument));
            }
        }
    }

    public function testRegistersCustomAliases(): void
    {
        $this->load(
            [
                Configuration::NODE_RENDERER => 'custom.renderer',
                Configuration::NODE_REDIRECTOR => 'custom.redirector',
                Configuration::NODE_EVENT_DISPATCHER => 'custom.event_dispatcher',
            ],
        );

        $this->assertContainerBuilderHasAlias(RendererInterface::class, 'custom.renderer');
        $this->assertContainerBuilderHasAlias(RedirectorInterface::class, 'custom.redirector');
        $this->assertContainerBuilderHasAlias(EventDispatcherInterface::class, 'custom.event_dispatcher');
    }

    public function testRegisterForAutoconfiguration(): void
    {
        $this->load();

        $autoconfiguredInstanceof = $this->container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(ExceptionNormalizerInterface::class, $autoconfiguredInstanceof);

        $definition = $autoconfiguredInstanceof[ExceptionNormalizerInterface::class];

        self::assertTrue($definition->hasTag('stepped_form.exception_normalizer'));
    }

    public function testRegisterWithoutFormDefinitions(): void
    {
        $this->load();

        self::assertFalse($this->container->hasAlias(SteppedFormInterface::class));
    }

    public function testRegisterSingleFormDefinition(): void
    {
        $this->load(
            [
                Configuration::NODE_FORMS => [
                    'form1' => [
                        Configuration::NODE_FORM_SETTINGS => FormSettings::class,
                        Configuration::NODE_FORM_BUILDER => 'custom.builder',
                        Configuration::NODE_STORAGE => [
                            'class' => 'custom.storage.alias',
                        ],
                        Configuration::NODE_SESSION_KEY_STORAGE => [
                            'class' => 'custom.session_key_storage.alias',
                        ],
                    ],
                ],
            ],
        );

        $this->assertDefinitionArgument('stepped_form.form.form1', 0, SteppedForm::class, [
            0 => new Reference('stepped_form.form1.step_control'),
            1 => new Reference('stepped_form.form1.data_control'),
            2 => new Reference('custom.builder'),
            3 => new Reference(EventDispatcherInterface::class),
        ]);

        $this->assertContainerBuilderHasAlias(SteppedFormInterface::class, 'stepped_form.form.form1');
    }

    public function testRegistersFormDefinitions(): void
    {
        $this->load(
            [
                Configuration::NODE_FORMS => [
                    'form1' => [
                        Configuration::NODE_FORM_SETTINGS => FormSettings::class,
                        Configuration::NODE_FORM_BUILDER => FormBuilder::class,
                        Configuration::NODE_STORAGE => [
                            'class' => 'custom.storage.alias',
                        ],
                        Configuration::NODE_SESSION_KEY_STORAGE => [
                            'class' => 'custom.session_key_storage.alias',
                        ],
                    ],
                    [
                        Configuration::NODE_FORM_ALIAS => 'form2',
                        Configuration::NODE_FORM_SETTINGS => FormSettings::class,
                        Configuration::NODE_FORM_STEPS => [
                            'step1' => CustomStep::class,
                        ],
                        Configuration::NODE_STORAGE => [
                            'class' => Storage::class,
                            'arguments' => ['$custom' => 1],
                        ],
                        Configuration::NODE_SESSION_KEY_STORAGE => [
                            'class' => SessionKeyStorage::class,
                        ],
                    ],
                ],
            ],
        );

        // Form 1 services
        $this->assertContainerBuilderHasService('stepped_form.form1.form_storage', FormStorage::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form1.form_storage',
            0,
            new Reference('custom.storage.alias'),
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form1.form_storage',
            1,
            new Reference('custom.session_key_storage.alias'),
        );

        $this->assertContainerBuilderHasService('stepped_form.form1.data_control', DataControl::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form1.data_control',
            0,
            new Definition(DataStorage::class, [new Reference('stepped_form.form1.form_storage')]),
        );

        $this->assertContainerBuilderHasService('stepped_form.form1.step_control', StepControl::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form1.step_control',
            0,
            new Reference('stepped_form.form1.form_storage'),
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form1.steps_builder',
            0,
            new Reference('stepped_form.form1.step_control'),
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form1.steps_builder',
            1,
            new Reference('stepped_form.form1.data_control'),
        );

        $this->assertContainerBuilderHasService('stepped_form.form.form1', HttpSteppedForm::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form.form1',
            1,
            new Reference(FormSettings::class),
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form.form1',
            2,
            new Reference(RedirectorInterface::class),
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form.form1',
            3,
            new Reference(RendererInterface::class),
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form.form1',
            4,
            new Reference(ExceptionNormalizerInterface::class),
        );

        $builderDefinition = new Definition(
            FormBuilder::class,
            ['$builder' => new Reference('stepped_form.form1.steps_builder')],
        );
        $builderDefinition->setAutowired(true);

        $this->assertDefinitionArgument('stepped_form.form.form1', 0, SteppedForm::class, [
            0 => new Reference('stepped_form.form1.step_control'),
            1 => new Reference('stepped_form.form1.data_control'),
            2 => $builderDefinition,
            3 => new Reference(EventDispatcherInterface::class),
        ]);

        // Form 2 services
        $this->assertContainerBuilderHasService('stepped_form.form2.form_storage', FormStorage::class);

        $storageDefinition = new Definition(Storage::class, ['$namespace' => 'form2', '$custom' => 1]);
        $storageDefinition->setAutowired(true);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form2.form_storage',
            0,
            $storageDefinition,
        );

        $sessionKeyStorageDefinition = new Definition(SessionKeyStorage::class, ['$namespace' => 'form2']);
        $sessionKeyStorageDefinition->setAutowired(true);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form2.form_storage',
            1,
            $sessionKeyStorageDefinition,
        );

        $this->assertContainerBuilderHasService('stepped_form.form2.data_control', DataControl::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form2.data_control',
            0,
            new Definition(DataStorage::class, [new Reference('stepped_form.form2.form_storage')]),
        );

        $this->assertContainerBuilderHasService('stepped_form.form2.step_control', StepControl::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'stepped_form.form2.step_control',
            0,
            new Reference('stepped_form.form2.form_storage'),
        );

        $httpFormDefinition = $this->container->findDefinition('stepped_form.form.form2');

        /** @var Definition $formDefinition */
        $formDefinition = $httpFormDefinition->getArgument(0);
        /** @var Definition $formBuilder */
        $formBuilder = $formDefinition->getArgument(2);

        self::assertSame(['stepped_form.factory', 'createStaticStepsFormBuilder'], $formBuilder->getFactory());
        self::assertEquals(new Reference('stepped_form.form2.steps_builder'), $formBuilder->getArgument(0));
        self::assertEquals(['step1' => new Reference(CustomStep::class)], $formBuilder->getArgument(1));

        self::assertFalse($this->container->hasAlias(SteppedFormInterface::class));
    }

    /**
     * @param array<string|int, mixed> $expectedArguments
     */
    private function assertDefinitionArgument(
        string $serviceOd,
        int $index,
        string $expectedClass,
        array $expectedArguments = [],
    ): void {
        $definition = $this->container->findDefinition($serviceOd);

        /** @var Definition $actual */
        $actual = $definition->getArguments()[$index];

        self::assertSame($expectedClass, $actual->getClass());
        self::assertEquals($expectedArguments, $actual->getArguments());
    }
}
