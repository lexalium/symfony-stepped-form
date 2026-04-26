<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Renderer;

use Lexal\HttpSteppedForm\Renderer\RendererInterface;
use Lexal\SteppedForm\Step\TemplateDefinition;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class Renderer implements RendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(TemplateDefinition $definition): Response
    {
        $content = $this->twig->render($definition->template, $definition->data);

        return new Response($content);
    }
}
