<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests\Routing\Bag;

use Lexal\SymfonySteppedForm\Routing\Bag\ErrorBag;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

final class ErrorBagTest extends TestCase
{
    private ErrorBag $errorBag;

    protected function setUp(): void
    {
        $this->errorBag = new ErrorBag();
    }

    public function testGetName(): void
    {
        self::assertSame('stepped_form_errors', $this->errorBag->getName());
    }

    public function testGetStorageKey(): void
    {
        self::assertSame('_stepped_form_errors', $this->errorBag->getStorageKey());
    }

    public function testInitialize(): void
    {
        $array = ['new' => ['error1', 'error2'], 'display' => []];

        $this->errorBag->initialize($array);

        self::assertSame(['error1', 'error2'], $array['display']); // @phpstan-ignore-line
        self::assertSame([], $array['new']); // @phpstan-ignore-line
    }

    public function testInitializeWithoutNew(): void
    {
        $array = ['display' => ['old_error']];

        $this->errorBag->initialize($array);

        self::assertSame([], $array['display']); // @phpstan-ignore-line
        self::assertSame([], $array['new']); // @phpstan-ignore-line
    }

    public function testAdd(): void
    {
        $array = ['new' => [], 'display' => []];

        $this->errorBag->initialize($array);

        $this->errorBag->add(['error1']);
        $this->errorBag->add(['error2']);

        self::assertSame(['error1', 'error2'], $array['new']); // @phpstan-ignore-line
    }

    public function testClear(): void
    {
        $array = ['new' => ['error1', 'error2'], 'display' => []];

        $this->errorBag->initialize($array);

        $cleared = $this->errorBag->clear();

        self::assertSame(['error1', 'error2'], $cleared);
        self::assertSame([], $array['display']); // @phpstan-ignore-line
    }

    public function testGetIterator(): void
    {
        $array = ['new' => ['error1'], 'display' => []];

        $this->errorBag->initialize($array);

        $iterator = $this->errorBag->getIterator();

        self::assertSame(['error1'], iterator_to_array($iterator));
    }

    public function testCount(): void
    {
        $array = ['new' => ['error1', 'error2'], 'display' => []];

        $this->errorBag->initialize($array);

        self::assertSame(2, $this->errorBag->count());
    }

    public function testCountAfterClear(): void
    {
        $array = ['new' => ['error1'], 'display' => []];

        $this->errorBag->initialize($array);

        $this->errorBag->clear();

        self::assertSame(0, $this->errorBag->count());
    }
}
