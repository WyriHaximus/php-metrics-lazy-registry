<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry\Registry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Gauge as GaugeInterface;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\LazyRegistry\Gauge;
use WyriHaximus\Metrics\Registry\Gauges as GaugesInterface;

use function array_map;

/** @api */
final class Gauges implements GaugesInterface
{
    private GaugesInterface|null $gauges = null;

    /** @var array<string> */
    private readonly array $requiredLabelNames;

    /** @var array<(callable(GaugesInterface): void)> */
    private array $queue = [];

    public function __construct(private readonly string $name, private readonly string $description, Name ...$requiredLabelNames)
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

    public function gauge(Label ...$labels): GaugeInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->gauges instanceof GaugesInterface) {
            return $this->gauges->gauge(...$labels);
        }

        $ghost         = new Gauge($this->name, $this->description, ...$labels);
        $this->queue[] = static fn (GaugesInterface $gauges) => $ghost->register($gauges->gauge(...$labels));

        return $ghost;
    }

    /** @return iterable<GaugeInterface> */
    public function gauges(): iterable
    {
        if ($this->gauges instanceof GaugesInterface) {
            yield from $this->gauges->gauges();
        }

        yield from [];
    }

    public function register(GaugesInterface $gauges): void
    {
        if ($this->gauges instanceof GaugesInterface) {
            throw new InvalidArgumentException();
        }

        $this->gauges = $gauges;

        foreach ($this->queue as $call) {
            $call($gauges);
        }

        $this->queue = [];
    }
}
