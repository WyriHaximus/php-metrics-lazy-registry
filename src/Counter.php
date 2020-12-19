<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Counter as CounterInterface;
use WyriHaximus\Metrics\Label;

use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Counter implements CounterInterface
{
    private ?CounterInterface $counter = null;

    private string $name;
    private string $description;
    /** @var array<Label> */
    private array $labels;

    /** @var array<string|array<mixed>> */
    private array $queue = [];

    public function __construct(string $name, string $description, Label ...$labels)
    {
        $this->name        = $name;
        $this->description = $description;
        $this->labels      = $labels;
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

        return ZERO;
    }

    /**
     * @return array<Label>
     */
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

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function incrBy(int $incr): void
    {
        if ($this->counter instanceof CounterInterface) {
            $this->counter->incrBy($incr);

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function incrTo(int $count): void
    {
        if ($this->counter instanceof CounterInterface) {
            $this->counter->incrTo($count);

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function register(CounterInterface $counter): void
    {
        if ($this->counter instanceof CounterInterface) {
            throw new InvalidArgumentException();
        }

        $this->counter = $counter;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $this->counter->{$call[ZERO]}(...$call[ONE]); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
