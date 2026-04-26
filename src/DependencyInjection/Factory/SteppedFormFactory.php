<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\DependencyInjection\Factory;

use Lexal\SteppedForm\Form\Builder\StaticStepsFormBuilder;
use Lexal\SteppedForm\Step\Builder\StepsBuilderInterface;
use Lexal\SteppedForm\Step\StepInterface;

final class SteppedFormFactory
{
    /**
     * @template TEntity of object
     *
     * @param array<string, StepInterface<TEntity>> $steps
     */
    public function createStaticStepsFormBuilder(StepsBuilderInterface $builder, array $steps): StaticStepsFormBuilder
    {
        foreach ($steps as $key => $step) {
            $builder->add($key, $step);
        }

        return new StaticStepsFormBuilder($builder->get());
    }
}
