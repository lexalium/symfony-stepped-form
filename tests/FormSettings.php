<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Tests;

use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;
use Lexal\SteppedForm\Step\StepKey;

final class FormSettings implements FormSettingsInterface
{
    public function getStepUrl(StepKey $key): string
    {
        return (string) $key;
    }

    public function getUrlAfterFinish(): string
    {
        return '';
    }

    public function getUrlBeforeStart(): string
    {
        return '';
    }
}
