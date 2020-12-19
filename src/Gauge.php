<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Gauge as GaugeInterface;
use WyriHaximus\Metrics\Label;

use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Gauge implements GaugeInterface
{
    private ?GaugeInterface $gauge = null;

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

    public function gauge(): int
    {
        if ($this->gauge instanceof GaugeInterface) {
            return $this->gauge->gauge();
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
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->incr();

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function incrBy(int $incr): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->incrBy($incr);

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function set(int $count): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->set($count);

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function dcrBy(int $dcr): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->dcrBy($dcr);

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function dcr(): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            $this->gauge->dcr();

            return;
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function register(GaugeInterface $gauge): void
    {
        if ($this->gauge instanceof GaugeInterface) {
            throw new InvalidArgumentException();
        }

        $this->gauge = $gauge;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $this->gauge->{$call[ZERO]}(...$call[ONE]); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
