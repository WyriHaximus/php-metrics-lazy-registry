<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Summary as SummaryInterface;
use WyriHaximus\Metrics\Summary\Quantile;

use function func_get_args;

final class Summary implements SummaryInterface
{
    private SummaryInterface|null $summary = null;

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

    /** @return iterable<Quantile> */
    public function quantiles(): iterable
    {
        yield from [];
    }

    /** @return array<Label> */
    public function labels(): array
    {
        return $this->labels;
    }

    public function observe(float $value): void
    {
        if ($this->summary instanceof SummaryInterface) {
            $this->summary->observe($value);
        }

        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args()];
    }

    public function register(SummaryInterface $summary): void
    {
        if ($this->summary instanceof SummaryInterface) {
            throw new InvalidArgumentException();
        }

        $this->summary = $summary;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $this->summary->{$call['function']}(...$call['args']); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
