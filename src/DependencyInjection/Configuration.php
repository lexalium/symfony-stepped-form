<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\DependencyInjection;

use Closure;
use InvalidArgumentException;
use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Form\Storage\NullSessionKeyStorage;
use Lexal\SteppedForm\Form\Storage\SessionKeyStorageInterface;
use Lexal\SteppedForm\Form\Storage\StorageInterface;
use Lexal\SteppedForm\Step\StepInterface;
use Lexal\SymfonySteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\SymfonySteppedForm\Renderer\Renderer;
use Lexal\SymfonySteppedForm\Routing\Redirector;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use function class_exists;
use function compact;
use function implode;
use function is_a;
use function preg_match;
use function sprintf;

final class Configuration implements ConfigurationInterface
{
    public const NODE_RENDERER = 'renderer';
    public const NODE_REDIRECTOR = 'redirector';
    public const NODE_EVENT_DISPATCHER = 'event_dispatcher';
    public const NODE_FORMS = 'forms';
    public const NODE_FORM_ALIAS = 'alias';
    public const NODE_FORM_BUILDER = 'form_builder';
    public const NODE_FORM_STEPS = 'steps';
    public const NODE_FORM_SETTINGS = 'form_settings';
    public const NODE_STORAGE = 'storage';
    public const NODE_SESSION_KEY_STORAGE = 'session_key_storage';
    public const NODE_STORAGE_CLASS = 'class';
    public const NODE_STORAGE_ARGUMENTS = 'arguments';

    private const FORM_KEY_PATTERN = '/^[A-Za-z0-9-_]+$/';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('stepped_form');

        $root = $treeBuilder->getRootNode();

        $root
            ->ignoreExtraKeys(false)
            ->fixXmlConfig('form')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode(self::NODE_RENDERER)
                    ->info('Class or service id to use as a form renderer.')
                    ->defaultValue(Renderer::class)
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifString()
                        ->then($this->getCheckIfClassImplementsClosure(RendererInterface::class))
                    ->end()
                ->end()
                ->scalarNode(self::NODE_REDIRECTOR)
                    ->info('Class or service id to use as a form redirector.')
                    ->defaultValue(Redirector::class)
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifString()
                        ->then($this->getCheckIfClassImplementsClosure(RedirectorInterface::class))
                    ->end()
                ->end()
                ->scalarNode(self::NODE_EVENT_DISPATCHER)
                    ->info('Class or service id to use as a form event dispatcher.')
                    ->defaultValue(EventDispatcher::class)
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifString()
                        ->then($this->getCheckIfClassImplementsClosure(EventDispatcherInterface::class))
                    ->end()
                ->end()
                ->append($this->getFormsNode())
            ->end();

        return $treeBuilder;
    }

    private function getFormsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('forms');

        return $treeBuilder->getRootNode()
            ->useAttributeAsKey(self::NODE_FORM_ALIAS)
            ->validate()
                ->ifArray()
                ->then(static function (array $forms): array {
                    foreach ($forms as $alias => $form) {
                        $alias = $form[self::NODE_FORM_ALIAS] ?? $alias;

                        if (!preg_match(self::FORM_KEY_PATTERN, $alias)) {
                            throw new InvalidConfigurationException(
                                sprintf(
                                    'The form alias must have only "A-z", "0-9", "-" and "_". Given: "%s".',
                                    $alias,
                                ),
                            );
                        }
                    }

                    return $forms;
                })
            ->end()
            ->arrayPrototype()
                ->info('Forms configuration.')
                ->validate()
                    ->ifArray()
                    ->then(static function (array $form): array {
                        if (!isset($form[self::NODE_FORM_BUILDER]) && empty($form[self::NODE_FORM_STEPS])) {
                            throw new InvalidConfigurationException(
                                sprintf(
                                    'The form definition must have at least one of the following parameters: %s.',
                                    implode(', ', [self::NODE_FORM_BUILDER, self::NODE_FORM_STEPS]),
                                ),
                            );
                        }

                        return $form;
                    })
                ->end()
                ->fixXmlConfig('step')
                ->children()
                    ->scalarNode(self::NODE_FORM_SETTINGS)
                        ->info('Class or service id to use as form settings.')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifString()
                            ->then($this->getCheckIfClassImplementsClosure(FormSettingsInterface::class))
                        ->end()
                    ->end()
                    ->scalarNode(self::NODE_FORM_BUILDER)
                        ->info('Class or service id to use as form builder.')
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifString()
                            ->then($this->getCheckIfClassImplementsClosure(FormBuilderInterface::class))
                        ->end()
                    ->end()
                    ->arrayNode(self::NODE_FORM_STEPS)
                        ->info('Array of step classes to use in a form.')
                        ->normalizeKeys(false)
                        ->useAttributeAsKey('key')
                        ->stringPrototype()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifString()
                                ->then($this->getCheckIfClassImplementsClosure(StepInterface::class))
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode(self::NODE_STORAGE)
                        ->info('Class, service id and optional arguments array to use as form data storage.')
                        ->isRequired()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static fn (string $class): array => compact('class'))
                        ->end()
                        ->fixXmlConfig('argument')
                        ->children()
                            ->scalarNode(self::NODE_STORAGE_CLASS)
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifString()
                                    ->then($this->getCheckIfClassImplementsClosure(StorageInterface::class))
                                ->end()
                            ->end()
                            ->arrayNode(self::NODE_STORAGE_ARGUMENTS)
                                ->useAttributeAsKey('name')
                                ->variablePrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode(self::NODE_SESSION_KEY_STORAGE)
                        ->info('Class, service id and optional arguments array to use as form session key storage.')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static fn (string $class): array => compact('class'))
                        ->end()
                        ->fixXmlConfig('argument')
                        ->children()
                            ->scalarNode(self::NODE_STORAGE_CLASS)
                                ->defaultValue(NullSessionKeyStorage::class)
                                ->cannotBeEmpty()
                                ->validate()
                                    ->ifString()
                                    ->then($this->getCheckIfClassImplementsClosure(SessionKeyStorageInterface::class))
                                ->end()
                            ->end()
                            ->arrayNode(self::NODE_STORAGE_ARGUMENTS)
                                ->useAttributeAsKey('name')
                                ->variablePrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function getCheckIfClassImplementsClosure(string $interface): Closure
    {
        return static function (string $class) use ($interface): string {
            if (class_exists($class) && !is_a($class, $interface, true)) {
                throw new InvalidArgumentException(
                    sprintf('Class "%s" must implement "%s" interface.', $class, $interface),
                );
            }

            return $class;
        };
    }
}
