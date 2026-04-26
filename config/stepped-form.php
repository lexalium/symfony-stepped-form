<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lexal\SymfonySteppedForm\DependencyInjection\Factory\SteppedFormFactory;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('stepped_form.factory', SteppedFormFactory::class)
    ;
};
