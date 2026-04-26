<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests;

use Lexal\SteppedForm\Form\Storage\SessionKeyStorageInterface;

final class SessionKeyStorage implements SessionKeyStorageInterface
{
    public function get(string $key): string
    {
        return '';
    }

    public function put(string $key, string $session): void
    {
        // nothing to do
    }
}
