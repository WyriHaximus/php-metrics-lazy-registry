<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry\Registry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Histogram as HistogramInterface;
use WyriHaximus\Metrics\Histogram\Buckets;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\LazyRegistry\Histogram;
use WyriHaximus\Metrics\Registry\Gauges as GaugesInterface;
use WyriHaximus\Metrics\Registry\Histograms as HistogramsInterface;

use function array_map;

/** @api */
final class Histograms implements HistogramsInterface
{
    private HistogramsInterface|null $histograms = null;

    /** @var array<string> */
    private readonly array $requiredLabelNames;

    /** @var array<(callable(HistogramsInterface): void)> */
    private array $queue = [];

    public function __construct(private readonly string $name, private readonly string $description, private readonly Buckets $buckets, Name ...$requiredLabelNames)
    {
        $this->requiredLabelNames = array_map(static fn (Name $name): string => $name->name(), $requiredLabelNames);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function histogram(Label ...$labels): HistogramInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->histograms instanceof GaugesInterface) {
            return $this->histograms->histogram(...$labels);
        }

        $ghost         = new Histogram($this->name, $this->description, $this->buckets, ...$labels);
        $this->queue[] = static fn (HistogramsInterface $histograms) => $ghost->register($histograms->histogram(...$labels));

        return $ghost;
    }

    /** @return iterable<HistogramInterface> */
    public function histograms(): iterable
    {
        if ($this->histograms instanceof HistogramsInterface) {
            yield from $this->histograms->histograms();
        }

        yield from [];
    }

    public function register(HistogramsInterface $histograms): void
    {
        if ($this->histograms instanceof HistogramsInterface) {
            throw new InvalidArgumentException();
        }

        $this->histograms = $histograms;

        foreach ($this->queue as $call) {
            $call($histograms);
        }

        $this->queue = [];
    }
}
