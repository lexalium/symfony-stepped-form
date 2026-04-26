<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests\Routing;

use Lexal\SymfonySteppedForm\Routing\Bag\ErrorBagInterface;
use Lexal\SymfonySteppedForm\Routing\Redirector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class RedirectorTest extends TestCase
{
    private MockObject&ErrorBagInterface $errorBag;
    private Redirector $redirector;

    protected function setUp(): void
    {
        $this->errorBag = $this->createMock(ErrorBagInterface::class);

        $this->redirector = new Redirector($this->errorBag);
    }

    public function testRedirectWithoutErrors(): void
    {
        $url = 'https://example.com';

        $this->errorBag->expects(self::never())
            ->method('add');

        $response = $this->redirector->redirect($url);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($url, $response->getTargetUrl());
    }

    public function testRedirectWithErrors(): void
    {
        $url = 'https://example.com';
        $errors = ['error1', 'error2'];

        $this->errorBag->expects(self::once())
            ->method('add')
            ->with($errors);

        $response = $this->redirector->redirect($url, $errors);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($url, $response->getTargetUrl());
    }
}
