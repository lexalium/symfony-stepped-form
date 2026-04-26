<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Routing\Bag;

use Countable;
use IteratorAggregate;

/**
 * @template-extends IteratorAggregate<int, string>
 */
interface ErrorBagInterface extends IteratorAggregate, Countable
{
    /**
     * @param string[] $errors
     */
    public function add(array $errors): void;
}
