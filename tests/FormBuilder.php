<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests;

use Lexal\SteppedForm\Form\Builder\FormBuilderInterface;
use Lexal\SteppedForm\Step\Step;
use Lexal\SteppedForm\Step\StepKey;
use Lexal\SteppedForm\Step\Steps;

/**
 * @template-implements FormBuilderInterface<object>
 */
final class FormBuilder implements FormBuilderInterface
{
    public function isDynamic(): bool
    {
        return false;
    }

    public function build(object $entity): Steps
    {
        return new Steps([
            new Step(new StepKey('step1'), new CustomStep()),
        ]);
    }
}
