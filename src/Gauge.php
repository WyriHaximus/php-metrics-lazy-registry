<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Gauge as GaugeInterface;
use WyriHaximus\Metrics\Label;

use function func_get_args;

final class Gauge implements GaugeInterface
{
    private const int DEFAULT_COUNT    = 0;
    private GaugeInterface|null $gauge = null;

    /** @var array<Label> */
    private readonly array $labels;

    /** @var array<array{function: string, args: array<mixed>}> */
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

    public function gauge(): int
    {
        if ($this->gauge instanceof GaugeInterface) {
            return $this->gauge->gauge();
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
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->incr();

            return;
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function incrBy(int $incr): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->incrBy($incr);

            return;
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function set(int $count): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->set($count);

            return;
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function dcrBy(int $dcr): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->dcrBy($dcr);

            return;
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function dcr(): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->dcr();

            return;
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function register(GaugeInterface $gauge): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            throw new InvalidArgumentException();
        }

        $this->gauge = $gauge;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $this->gauge->{$call['function']}(...$call['args']); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
