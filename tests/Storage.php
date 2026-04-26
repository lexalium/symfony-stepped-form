<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests;

use Lexal\SteppedForm\Form\Storage\StorageInterface;

final class Storage implements StorageInterface
{
    public function get(string $key, string $session, mixed $default = null): mixed
    {
        return $default;
    }

    public function put(string $key, string $session, mixed $data): void
    {
        // nothing to do
    }

    public function clear(string $session): void
    {
        // nothing to do
    }
}
