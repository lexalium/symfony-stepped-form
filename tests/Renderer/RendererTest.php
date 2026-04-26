<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests\Renderer;

use Lexal\SteppedForm\Step\TemplateDefinition;
use Lexal\SymfonySteppedForm\Renderer\Renderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class RendererTest extends TestCase
{
    private MockObject&Environment $twig;
    private Renderer $renderer;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $this->renderer = new Renderer($this->twig);
    }

    public function testRender(): void
    {
        $template = 'template.html.twig';
        $data = ['key' => 'value'];
        $content = 'twig test content';

        $definition = new TemplateDefinition($template, $data);

        $this->twig->method('render')
            ->with($template, $data)
            ->willReturn($content);

        $response = $this->renderer->render($definition);

        self::assertSame($content, $response->getContent());
    }
}
