<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\DependencyInjection;

use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\HttpSteppedForm\SteppedForm as HttpSteppedForm;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Lexal\SteppedForm\Form\DataControl;
use Lexal\SteppedForm\Form\StepControl;
use Lexal\SteppedForm\Form\Storage\DataStorage;
use Lexal\SteppedForm\Form\Storage\FormStorage;
use Lexal\SteppedForm\Step\Builder\StepsBuilder;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizerInterface;
use Lexal\HttpSteppedForm\SteppedFormInterface;
use Lexal\SteppedForm\SteppedForm;
use Lexal\SymfonySteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\SymfonySteppedForm\Renderer\Renderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function array_map;
use function array_merge;
use function class_exists;
use function count;
use function dirname;
use function sprintf;

final class SteppedFormExtension extends Extension
{
    public function getNamespace(): string
    {
        return 'https://lexalium.org/schema/dic/stepped-form';
    }

    public function getXsdValidationBasePath(): string
    {
        return dirname(__DIR__, 2) . '/config/schema';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__, 2) . '/config'));

        $loader->load('stepped-form.php');
        $loader->load('exception-normalizer.php');

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias(RendererInterface::class, $config[Configuration::NODE_RENDERER]);
        $container->setAlias(RedirectorInterface::class, $config[Configuration::NODE_REDIRECTOR]);
        $container->setAlias(EventDispatcherInterface::class, $config[Configuration::NODE_EVENT_DISPATCHER]);

        if ($config[Configuration::NODE_EVENT_DISPATCHER] === EventDispatcher::class) {
            $container->setParameter('stepped_form.event_dispatcher_needed', true);
        }

        if ($config[Configuration::NODE_RENDERER] === Renderer::class) {
            $container->setParameter('stepped_form.twig_needed', true);
        }

        $lastFormAlias = null;

        foreach ($config[Configuration::NODE_FORMS] as $alias => $form) {
            $alias = $lastFormAlias = $form[Configuration::NODE_FORM_ALIAS] ?? $alias;

            $formStorage = $this->registerFormStorage($container, $alias, $form);
            $this->registerFormControllers($container, $alias, $formStorage);

            $steppedFormDefinition = new Definition(SteppedForm::class);

            $steppedFormDefinition->addArgument(new Reference(sprintf('stepped_form.%s.step_control', $alias)));
            $steppedFormDefinition->addArgument(new Reference(sprintf('stepped_form.%s.data_control', $alias)));
            $steppedFormDefinition->addArgument($this->registerFormBuilder($container, $alias, $form));
            $steppedFormDefinition->addArgument(new Reference(EventDispatcherInterface::class));

            $httpSteppedFormDefinition = new Definition(HttpSteppedForm::class);

            $httpSteppedFormDefinition->addArgument($steppedFormDefinition);
            $httpSteppedFormDefinition->addArgument(new Reference($form[Configuration::NODE_FORM_SETTINGS]));
            $httpSteppedFormDefinition->addArgument(new Reference(RedirectorInterface::class));
            $httpSteppedFormDefinition->addArgument(new Reference(RendererInterface::class));
            $httpSteppedFormDefinition->addArgument(new Reference(ExceptionNormalizerInterface::class));

            $container->setDefinition(sprintf('stepped_form.form.%s', $alias), $httpSteppedFormDefinition);
        }

        if ($lastFormAlias !== null && count($config[Configuration::NODE_FORMS]) === 1) {
            $container->setAlias(SteppedFormInterface::class, sprintf('stepped_form.form.%s', $lastFormAlias));
        }

        $container->registerForAutoconfiguration(ExceptionNormalizerInterface::class)
            ->addTag('stepped_form.exception_normalizer');
    }

    /**
     * @param array<string, mixed> $form
     */
    private function registerFormStorage(ContainerBuilder $container, string $alias, array $form): Reference
    {
        $definition = new Definition(FormStorage::class);

        $definition->addArgument($this->getStorageDefinition($alias, $form[Configuration::NODE_STORAGE]));
        $definition->addArgument($this->getStorageDefinition($alias, $form[Configuration::NODE_SESSION_KEY_STORAGE]));

        $container->setDefinition($id = sprintf('stepped_form.%s.form_storage', $alias), $definition);

        return new Reference($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getStorageDefinition(string $alias, array $data): Reference|Definition
    {
        if (!class_exists($data[Configuration::NODE_STORAGE_CLASS])) {
            return new Reference($data[Configuration::NODE_STORAGE_CLASS]);
        }

        $arguments = array_merge(
            ['$namespace' => $alias],
            $data[Configuration::NODE_STORAGE_ARGUMENTS] ?? [],
        );

        $definition = new Definition($data[Configuration::NODE_STORAGE_CLASS], $arguments);
        $definition->setAutowired(true);

        return $definition;
    }

    /**
     * @param array<string, mixed> $form
     */
    private function registerFormBuilder(ContainerBuilder $container, string $alias, array $form): Reference|Definition
    {
        $stepsBuilderReference = $this->registerStepsBuilder($container, $alias);

        return isset($form[Configuration::NODE_FORM_BUILDER])
            ? $this->getFormBuilderFromClass($form[Configuration::NODE_FORM_BUILDER], $stepsBuilderReference)
            : $this->getFormBuilderFromSteps($form[Configuration::NODE_FORM_STEPS], $stepsBuilderReference);
    }

    private function getFormBuilderFromClass(string $class, Reference $stepsBuilder): Reference|Definition
    {
        if (!class_exists($class)) {
            return new Reference($class);
        }

        $definition = new Definition($class, ['$builder' => $stepsBuilder]);
        $definition->setAutowired(true);

        return $definition;
    }

    /**
     * @param array<string, string> $steps
     */
    private function getFormBuilderFromSteps(array $steps, Reference $builder): Definition
    {
        $definition = new Definition();

        $definition->setFactory(['stepped_form.factory', 'createStaticStepsFormBuilder'])
            ->addArgument($builder)
            ->addArgument(array_map(static fn (string $step): Reference => new Reference($step), $steps))
        ;

        return $definition;
    }

    private function registerStepsBuilder(ContainerBuilder $container, string $alias): Reference
    {
        $definition = new Definition(StepsBuilder::class);

        $definition->addArgument(new Reference(sprintf('stepped_form.%s.step_control', $alias)));
        $definition->addArgument(new Reference(sprintf('stepped_form.%s.data_control', $alias)));

        $container->setDefinition($id = sprintf('stepped_form.%s.steps_builder', $alias), $definition);

        return new Reference($id);
    }

    private function registerFormControllers(ContainerBuilder $container, string $alias, Reference $formStorage): void
    {
        $dataControlDefinition = new Definition(DataControl::class, [
            new Definition(DataStorage::class, [$formStorage]),
        ]);
        $stepControlDefinition = new Definition(StepControl::class, [$formStorage]);

        $container->addDefinitions([
            sprintf('stepped_form.%s.data_control', $alias) => $dataControlDefinition,
            sprintf('stepped_form.%s.step_control', $alias) => $stepControlDefinition,
        ]);
    }
}
