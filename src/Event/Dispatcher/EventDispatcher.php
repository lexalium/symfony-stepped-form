<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Event\Dispatcher;

use Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(private readonly SymfonyEventDispatcherInterface $dispatcher)
    {
    }

    public function dispatch(object $event): object
    {
        return $this->dispatcher->dispatch($event);
    }
}
