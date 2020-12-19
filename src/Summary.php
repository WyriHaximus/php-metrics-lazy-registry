<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Summary as SummaryInterface;
use WyriHaximus\Metrics\Summary\Quantile;

use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Summary implements SummaryInterface
{
    private ?SummaryInterface $summary = null;

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

    /**
     * @return iterable<Quantile>
     */
    public function quantiles(): iterable
    {
        yield from [];
    }

    /**
     * @return array<Label>
     */
    public function labels(): array
    {
        return $this->labels;
    }

    public function observe(float $value): void
    {
        if ($this->summary instanceof SummaryInterface) {
            $this->summary->observe(...func_get_args());
        }

        $this->queue[] = [__FUNCTION__, func_get_args()];
    }

    public function register(SummaryInterface $summary): void
    {
        if ($this->summary instanceof SummaryInterface) {
            throw new InvalidArgumentException();
        }

        $this->summary = $summary;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $this->summary->{$call[ZERO]}(...$call[ONE]); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
