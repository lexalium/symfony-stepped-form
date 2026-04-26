<?php

declare(strict_types=1);

namespace Lexal\SymfonySteppedForm\Routing\Bag;

use ArrayIterator;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

use function array_merge;
use function count;

final class ErrorBag implements ErrorBagInterface, SessionBagInterface
{
    private const STORAGE_KEY = '_stepped_form_errors';
    private const BAG_NAME = 'stepped_form_errors';

    /**
     * @var array{new: string[], display: string[]}
     */
    private array $errors = ['display' => [], 'new' => []];

    public function getName(): string
    {
        return self::BAG_NAME;
    }

    public function getStorageKey(): string
    {
        return self::STORAGE_KEY;
    }

    /**
     * @param array{new?: string[], display?: string[]} $array
     */
    public function initialize(array &$array): void
    {
        $this->errors = &$array; // @phpstan-ignore-line

        $display = $this->errors['new'] ?? [];

        $this->errors['display'] = $display;
        $this->errors['new'] = [];
    }

    public function add(array $errors): void
    {
        $this->errors['new'] = array_merge($this->errors['new'], $errors);
    }

    /**
     * @inheritDoc
     *
     * @return string[]
     */
    public function clear(): array
    {
        $errors = $this->errors['display'];

        $this->errors['display'] = [];

        return $errors;
    }

    /**
     * @return ArrayIterator<int, string>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errors['display']);
    }

    public function count(): int
    {
        return count($this->errors['display']);
    }
}
