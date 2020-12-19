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
use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\TWO;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Gauges implements GaugesInterface
{
    private ?GaugesInterface $gauges = null;

    private string $name;
    private string $description;
    /** @var array<string> */
    private array $requiredLabelNames;

    /** @var array<string|array<mixed>> */
    private array $queue = [];

    public function __construct(string $name, string $description, Name ...$requiredLabelNames)
    {
        $this->name               = $name;
        $this->description        = $description;
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

    public function gauge(Label ...$labels): GaugeInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->gauges instanceof GaugesInterface) {
            return $this->gauges->gauge(...$labels);
        }

        $ghost         = new Gauge($this->name, $this->description, ...$labels);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    /**
     * @return iterable<GaugeInterface>
     */
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
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $call[TWO]->register($this->gauges->{$call[ZERO]}(...$call[ONE])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
