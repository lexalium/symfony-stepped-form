<?php

declare(strict_types=1);

namespace DependencyInjection\Factory;

use Lexal\SteppedForm\Form\DataControlInterface;
use Lexal\SteppedForm\Form\StepControlInterface;
use Lexal\SteppedForm\Step\Builder\StepsBuilder;
use Lexal\SteppedForm\Step\LazyStep;
use Lexal\SteppedForm\Step\StepInterface;
use Lexal\SteppedForm\Step\StepKey;
use Lexal\SteppedForm\Step\Steps;
use Lexal\SymfonySteppedForm\DependencyInjection\Factory\SteppedFormFactory;
use PHPUnit\Framework\TestCase;
use stdClass;

final class SteppedFormFactoryTest extends TestCase
{
    public function testCreateStaticStepsFormBuilder(): void
    {
        $factory = new SteppedFormFactory();

        $stepControl = $this->createStub(StepControlInterface::class);
        $dataControl = $this->createStub(DataControlInterface::class);

        $stepControl->method('getCurrent')->willReturn('key');
        $dataControl->method('hasStepEntity')->willReturn(true);

        $step1 = $this->createMock(StepInterface::class);
        $step2 = $this->createMock(StepInterface::class);

        $isCurrentCallback = static fn (): bool => false;
        $isSubmittedCallback = static fn (): bool => false;

        $formBuilder = $factory->createStaticStepsFormBuilder(
            new StepsBuilder($stepControl, $dataControl),
            ['step1' => $step1, 'step2' => $step2],
        );

        self::assertEquals(
            new Steps([
                'step1' => new LazyStep(new StepKey('step1'), $step1, $isCurrentCallback, $isSubmittedCallback),
                'step2' => new LazyStep(new StepKey('step2'), $step2, $isCurrentCallback, $isSubmittedCallback),
            ]),
            $formBuilder->build(new stdClass()),
        );
    }
}
