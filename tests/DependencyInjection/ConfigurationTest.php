<?php

declare(strict_types=1);

namespace DependencyInjection;

use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;
use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Form\Storage\SessionKeyStorageInterface;
use Lexal\SteppedForm\Form\Storage\StorageInterface;
use Lexal\SteppedForm\Step\StepInterface;
use Lexal\SymfonySteppedForm\DependencyInjection\Configuration;
use Lexal\SymfonySteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\SymfonySteppedForm\Renderer\Renderer;
use Lexal\SymfonySteppedForm\Routing\Redirector;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function implode;
use function sprintf;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $expected
     */
    #[DataProvider('validConfigurationProvider')]
    public function testValidConfiguration(array $config, array $expected): void
    {
        $this->assertProcessedConfigurationEquals([$config], $expected);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
     */
    public static function validConfigurationProvider(): iterable
    {
        yield 'defaults' => [
            [],
            [
                Configuration::NODE_RENDERER => Renderer::class,
                Configuration::NODE_REDIRECTOR => Redirector::class,
                Configuration::NODE_EVENT_DISPATCHER => EventDispatcher::class,
                Configuration::NODE_FORMS => [],
            ],
        ];

        yield 'form storage as array' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => [
                            'class' => 'custom.form_storage',
                            'arguments' => [
                                '$custom' => 1,
                            ],
                        ],
                        Configuration::NODE_SESSION_KEY_STORAGE => [
                            'class' => 'custom.form_storage',
                            'arguments' => [
                                '$custom' => 'alias',
                            ],
                        ],
                    ],
                ],
            ],
            [
                Configuration::NODE_RENDERER => Renderer::class,
                Configuration::NODE_REDIRECTOR => Redirector::class,
                Configuration::NODE_EVENT_DISPATCHER => EventDispatcher::class,
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_FORM_STEPS => [],
                        Configuration::NODE_STORAGE => [
                            'class' => 'custom.form_storage',
                            'arguments' => [
                                '$custom' => 1,
                            ],
                        ],
                        Configuration::NODE_SESSION_KEY_STORAGE => [
                            'class' => 'custom.form_storage',
                            'arguments' => [
                                '$custom' => 'alias',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'form storage as string' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_STEPS => [
                            'step-1' => 'custom.step1',
                        ],
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                        Configuration::NODE_SESSION_KEY_STORAGE => 'custom.form_session_key_storage',
                    ],
                ],
            ],
            [
                Configuration::NODE_RENDERER => Renderer::class,
                Configuration::NODE_REDIRECTOR => Redirector::class,
                Configuration::NODE_EVENT_DISPATCHER => EventDispatcher::class,
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_STEPS => [
                            'step-1' => 'custom.step1',
                        ],
                        Configuration::NODE_STORAGE => [
                            'class' => 'custom.form_storage',
                            'arguments' => [],
                        ],
                        Configuration::NODE_SESSION_KEY_STORAGE => [
                            'class' => 'custom.form_session_key_storage',
                            'arguments' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidWhenEmptyValuesDataProvider')]
    public function testInvalidWhenEmptyValues(array $config, string $node): void
    {
        $this->assertConfigurationIsInvalid([$config], $node);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidWhenEmptyValuesDataProvider(): iterable
    {
        yield 'empty renderer' => [
            [Configuration::NODE_RENDERER => ''],
            Configuration::NODE_RENDERER,
        ];

        yield 'empty redirector' => [
            [Configuration::NODE_REDIRECTOR => ''],
            Configuration::NODE_REDIRECTOR,
        ];

        yield 'empty event dispatcher' => [
            [Configuration::NODE_EVENT_DISPATCHER => ''],
            Configuration::NODE_EVENT_DISPATCHER,
        ];

        yield 'empty form settings' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => '',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                    ],
                ],
            ],
            Configuration::NODE_FORM_SETTINGS,
        ];

        yield 'empty form builder' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => '',
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                    ],
                ],
            ],
            Configuration::NODE_FORM_BUILDER,
        ];

        yield 'empty form step' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_STEPS => [
                            'step1' => '',
                        ],
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                    ],
                ],
            ],
            Configuration::NODE_FORM_STEPS,
        ];

        yield 'empty form storage' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => '',
                    ],
                ],
            ],
            Configuration::NODE_STORAGE,
        ];

        yield 'empty form session key storage' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                        Configuration::NODE_SESSION_KEY_STORAGE => '',
                    ],
                ],
            ],
            Configuration::NODE_SESSION_KEY_STORAGE,
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidWhenDoesNotImplementDataProvider')]
    public function testInvalidWhenDoesNotImplement(array $config, string $interface): void
    {
        $this->assertConfigurationIsInvalid(
            [$config],
            sprintf('Class "%s" must implement "%s" interface.', stdClass::class, $interface),
        );
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidWhenDoesNotImplementDataProvider(): iterable
    {
        yield 'invalid renderer' => [
            [
                Configuration::NODE_RENDERER => stdClass::class,
            ],
            RendererInterface::class,
        ];

        yield 'invalid redirector' => [
            [
                Configuration::NODE_REDIRECTOR => stdClass::class,
            ],
            RedirectorInterface::class,
        ];

        yield 'invalid event dispatcher' => [
            [
                Configuration::NODE_EVENT_DISPATCHER => stdClass::class,
            ],
            EventDispatcherInterface::class,
        ];

        yield 'invalid form settings' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => stdClass::class,
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                    ],
                ],
            ],
            FormSettingsInterface::class,
        ];

        yield 'invalid form builder' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => stdClass::class,
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                    ],
                ],
            ],
            FormBuilderInterface::class,
        ];

        yield 'invalid form storage' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => stdClass::class,
                    ],
                ],
            ],
            StorageInterface::class,
        ];

        yield 'invalid form session key storage' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                        Configuration::NODE_SESSION_KEY_STORAGE => stdClass::class,
                    ],
                ],
            ],
            SessionKeyStorageInterface::class,
        ];

        yield 'invalid form step' => [
            [
                Configuration::NODE_FORMS => [
                    'key' => [
                        Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                        Configuration::NODE_FORM_STEPS => [
                            'step1' => stdClass::class,
                        ],
                        Configuration::NODE_STORAGE => 'custom.form_storage',
                    ],
                ],
            ],
            StepInterface::class,
        ];
    }

    public function testInvalidFormKey(): void
    {
        $this->assertConfigurationIsInvalid(
            [
                [
                    Configuration::NODE_FORMS => [
                        'invalid key' => [
                            Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                            Configuration::NODE_FORM_BUILDER => 'custom.form_builder',
                            Configuration::NODE_STORAGE => 'custom.form_storage',
                        ],
                    ],
                ],
            ],
            'The form alias must have only "A-z", "0-9", "-" and "_". Given: "invalid key".',
        );
    }

    public function testInvalidWhenMissedStepsBuilder(): void
    {
        $this->assertConfigurationIsInvalid(
            [
                [
                    Configuration::NODE_FORMS => [
                        'key' => [
                            Configuration::NODE_FORM_SETTINGS => 'custom.form_settings',
                            Configuration::NODE_STORAGE => 'custom.form_storage',
                        ],
                    ],
                ],
            ],
            sprintf(
                'The form definition must have at least one of the following parameters: %s.',
                implode(', ', [Configuration::NODE_FORM_BUILDER, Configuration::NODE_FORM_STEPS]),
            ),
        );
    }
}
