<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\ExceptionNormalizerInterface;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\AlreadyStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\DefaultExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\EntityNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\FormNotStartedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotFoundExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotRenderableExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\StepNotSubmittedExceptionNormalizer;
use Lexal\HttpSteppedForm\ExceptionNormalizer\Normalizers\SteppedFormErrorsExceptionNormalizer;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('stepped_form.exception_normalizer.already_started', AlreadyStartedExceptionNormalizer::class)
            ->args([service('stepped_form.redirector')])
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.step_not_found', StepNotFoundExceptionNormalizer::class)
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.step_not_renderable', StepNotRenderableExceptionNormalizer::class)
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.step_not_submitted', StepNotSubmittedExceptionNormalizer::class)
            ->args([service('stepped_form.redirector')])
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.entity_not_found', EntityNotFoundExceptionNormalizer::class)
            ->args([service('stepped_form.redirector')])
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.form_not_started', FormNotStartedExceptionNormalizer::class)
            ->args([service('stepped_form.redirector')])
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.stepped_form_errors', SteppedFormErrorsExceptionNormalizer::class)
            ->args([service('stepped_form.redirector')])
            ->tag('stepped_form.exception_normalizer', ['priority' => -100])

        ->set('stepped_form.exception_normalizer.default', DefaultExceptionNormalizer::class)
            ->tag('stepped_form.exception_normalizer', ['priority' => -200])

        ->set('stepped_form.exception_normalizer', ExceptionNormalizer::class)
            ->args([abstract_arg('list of exception normalizers')])

        ->alias(ExceptionNormalizerInterface::class, 'stepped_form.exception_normalizer')
    ;
};
