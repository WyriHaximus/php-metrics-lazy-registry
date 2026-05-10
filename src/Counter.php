<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Counter as CounterInterface;
use WyriHaximus\Metrics\Label;

/** @api */
final class Counter implements CounterInterface
{
    private const int DEFAULT_COUNT = 0;

    private CounterInterface|null $counter = null;

    /** @var array<Label> */
    private readonly array $labels;

    /** @var array<(callable(CounterInterface): void)> */
    private array $queue = [];

    public function __construct(private readonly string $name, private readonly string $description, Label ...$labels)
    {
        $this->labels = $labels;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function count(): int
    {
        if ($this->counter instanceof CounterInterface) {
            return $this->counter->count();
        }

        return self::DEFAULT_COUNT;
    }

    /** @return array<Label> */
    public function labels(): array
    {
        return $this->labels;
    }

    public function incr(): void
    {
        if ($this->counter instanceof CounterInterface) {
            $this->counter->incr();

            return;
        }

        $this->queue[] = static fn (CounterInterface $counter) => $counter->incr();
    }

    public function incrBy(int $incr): void
    {
        if ($this->counter instanceof CounterInterface) {
            $this->counter->incrBy($incr);

            return;
        }

        $this->queue[] = static fn (CounterInterface $counter) => $counter->incrBy($incr);
    }

    public function incrTo(int $count): void
    {
        if ($this->counter instanceof CounterInterface) {
            $this->counter->incrTo($count);

            return;
        }

        $this->queue[] = static fn (CounterInterface $counter) => $counter->incrTo($count);
    }

    public function register(CounterInterface $counter): void
    {
        if ($this->counter instanceof CounterInterface) {
            throw new InvalidArgumentException();
        }

        $this->counter = $counter;

        foreach ($this->queue as $call) {
            $call($counter);
        }

        $this->queue = [];
    }
}
