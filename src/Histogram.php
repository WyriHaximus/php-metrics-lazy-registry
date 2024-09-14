<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Histogram as HistogramInterface;
use WyriHaximus\Metrics\Histogram\Bucket;
use WyriHaximus\Metrics\Histogram\Buckets;
use WyriHaximus\Metrics\Label;

use function array_map;
use function func_get_args;

final class Histogram implements HistogramInterface
{
    private const DEFAULT_COUNT                = 0;
    private HistogramInterface|null $histogram = null;

    /** @var array<Bucket> */
    private array $buckets;
    /** @var array<Label> */
    private array $labels;

    /** @var array<array{function: string, args: array<mixed>}> */
    private array $queue = [];

    public function __construct(private string $name, private string $description, Buckets $buckets, Label ...$labels)
    {
        $this->buckets = array_map(static fn (float $quantile) => new Bucket((string) $quantile), $buckets->buckets());
        $this->labels  = $labels;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /** @return iterable<Bucket> */
    public function buckets(): iterable
    {
        yield from $this->buckets;
        yield '+Inf' => Bucket::createWithCount('+Inf', self::DEFAULT_COUNT);
    }

    public function summary(): float
    {
        if ($this->histogram instanceof HistogramInterface) {
            return $this->histogram->summary();
        }

        return self::DEFAULT_COUNT;
    }

    public function count(): int
    {
        if ($this->histogram instanceof HistogramInterface) {
            return $this->histogram->count();
        }

        return self::DEFAULT_COUNT;
    }

    /** @return array<Label> */
    public function labels(): array
    {
        return $this->labels;
    }

    public function observe(float $value): void
    {
        if ($this->histogram instanceof HistogramInterface) {
            $this->histogram->observe($value);

            return;
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function register(HistogramInterface $histogram): void
    {
        if ($this->histogram instanceof HistogramInterface) {
            throw new InvalidArgumentException();
        }

        $this->histogram = $histogram;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $this->histogram->{$call['function']}(...$call['args']); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
