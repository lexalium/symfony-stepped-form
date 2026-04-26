<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests;

use Lexal\SteppedForm\Step\StepInterface;

/**
 * @template-implements StepInterface<object>
 */
final class CustomStep implements StepInterface
{
    public function handle(object $entity, mixed $data): object
    {
        return $entity;
    }
}
