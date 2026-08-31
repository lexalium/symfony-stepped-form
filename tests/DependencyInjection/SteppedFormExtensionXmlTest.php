<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests\DependencyInjection;

use Lexal\SymfonySteppedForm\DependencyInjection\Configuration;
use Lexal\SymfonySteppedForm\DependencyInjection\SteppedFormExtension;
use Lexal\SymfonySteppedForm\Event\Dispatcher\EventDispatcher;
use Lexal\SymfonySteppedForm\Routing\Redirector;
use Lexal\SymfonySteppedForm\Tests\FormSettings;
use Lexal\SymfonySteppedForm\Tests\Storage;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use PHPUnit\Framework\Attributes\RequiresMethod;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use function dirname;

#[RequiresMethod(XmlFileLoader::class, 'load')]
final class SteppedFormExtensionXmlTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension(): ExtensionInterface
    {
        return new SteppedFormExtension();
    }

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    public function testXMLLoadedCorrectly(): void
    {
        $this->assertProcessedConfigurationEquals(
            [
                'renderer' => 'custom.renderer',
                'redirector' => Redirector::class,
                'event_dispatcher' => EventDispatcher::class,
                'forms' => [
                    'form1' => [
                        'form_builder' => 'custom.form_builder',
                        'form_settings' => FormSettings::class,
                        'steps' => [
                            'step1' => 'custom.step1',
                            'step2' => 'custom.step2',
                        ],
                        'storage' => [
                            'class' => Storage::class,
                            'arguments' => ['$custom' => 1],
                        ],
                    ],
                ],
            ],
            [dirname(__DIR__) . '/config/stepped-form.xml'],
        );
    }
}
