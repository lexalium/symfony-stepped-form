<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Routing;

use Lexal\HttpSteppedForm\Routing\RedirectorInterface;
use Lexal\SymfonySteppedForm\Routing\Bag\ErrorBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class Redirector implements RedirectorInterface
{
    public function __construct(private readonly ErrorBagInterface $bag)
    {
    }

    public function redirect(string $url, array $errors = []): Response
    {
        $response = new RedirectResponse($url);

        if ($errors) {
            $this->bag->add($errors);
        }

        return $response;
    }
}
