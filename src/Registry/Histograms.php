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
use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\TWO;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Histograms implements HistogramsInterface
{
    private ?HistogramsInterface $histograms = null;

    private string $name;
    private string $description;
    private Buckets $buckets;
    /** @var array<string> */
    private array $requiredLabelNames;

    /** @var array<string|array<mixed>> */
    private array $queue = [];

    public function __construct(string $name, string $description, Buckets $buckets, Name ...$requiredLabelNames)
    {
        $this->name               = $name;
        $this->description        = $description;
        $this->buckets            = $buckets;
        $this->requiredLabelNames = array_map(static fn (Name $name) => $name->name(), $requiredLabelNames);
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
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    /**
     * @return iterable<HistogramInterface>
     */
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
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $call[TWO]->register($this->histograms->{$call[ZERO]}(...$call[ONE])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
