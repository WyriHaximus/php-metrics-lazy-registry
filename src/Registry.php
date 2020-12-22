<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Histogram\Buckets;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\LazyRegistry\Registry\Counters;
use WyriHaximus\Metrics\LazyRegistry\Registry\Gauges;
use WyriHaximus\Metrics\LazyRegistry\Registry\Histograms;
use WyriHaximus\Metrics\LazyRegistry\Registry\Summaries;
use WyriHaximus\Metrics\Printer;
use WyriHaximus\Metrics\Registry as RegistryInterface;
use WyriHaximus\Metrics\Registry\Counters as CountersInterface;
use WyriHaximus\Metrics\Registry\Gauges as GaugesInterface;
use WyriHaximus\Metrics\Registry\Histograms as HistogramsInterface;
use WyriHaximus\Metrics\Registry\Summaries as SummariesInterface;
use WyriHaximus\Metrics\Summary\Quantiles;

use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\TWO;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Registry implements RegistryInterface
{
    private ?RegistryInterface $registry = null;

    /** @var array<string|array<mixed>> */
    private array $queue = [];

    public function counter(string $name, string $description, Name ...$requiredLabelNames): CountersInterface
    {
        if ($this->registry instanceof RegistryInterface) {
            return $this->registry->counter($name, $description, ...$requiredLabelNames);
        }

        $ghost         = new Counters($name, $description, ...$requiredLabelNames);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    public function gauge(string $name, string $description, Name ...$requiredLabelNames): GaugesInterface
    {
        if ($this->registry instanceof RegistryInterface) {
            return $this->registry->gauge($name, $description, ...$requiredLabelNames);
        }

        $ghost         = new Gauges($name, $description, ...$requiredLabelNames);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    public function histogram(string $name, string $description, Buckets $buckets, Name ...$requiredLabelNames): HistogramsInterface
    {
        if ($this->registry instanceof RegistryInterface) {
            return $this->registry->histogram($name, $description, $buckets, ...$requiredLabelNames);
        }

        $ghost         = new Histograms($name, $description, $buckets, ...$requiredLabelNames);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    public function summary(string $name, string $description, Quantiles $quantiles, Name ...$requiredLabelNames): SummariesInterface
    {
        if ($this->registry instanceof RegistryInterface) {
            return $this->registry->summary($name, $description, $quantiles, ...$requiredLabelNames);
        }

        $ghost         = new Summaries($name, $description, ...$requiredLabelNames);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    public function print(Printer $printer): string
    {
        if ($this->registry instanceof RegistryInterface) {
            return $this->registry->print($printer);
        }

        return '';
    }

    public function register(RegistryInterface $registry): void
    {
        if ($this->registry instanceof RegistryInterface) {
            throw new InvalidArgumentException();
        }

        $this->registry = $registry;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $call[TWO]->register($this->registry->{$call[ZERO]}(...$call[ONE])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
