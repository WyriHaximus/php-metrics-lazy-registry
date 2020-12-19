<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry\Registry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Counter as CounterInterface;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\LazyRegistry\Counter;
use WyriHaximus\Metrics\Registry\Counters as CountersInterface;

use function array_map;
use function func_get_args;

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\TWO;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Counters implements CountersInterface
{
    private ?CountersInterface $counters = null;
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

    public function counter(Label ...$labels): CounterInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->counters instanceof CountersInterface) {
            return $this->counters->counter(...$labels);
        }

        $ghost         = new Counter($this->name, $this->description, ...$labels);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    /**
     * @return iterable<CounterInterface>
     */
    public function counters(): iterable
    {
        if ($this->counters instanceof CountersInterface) {
            yield from $this->counters->counters();
        }

        yield from [];
    }

    public function register(CountersInterface $counters): void
    {
        if ($this->counters instanceof CountersInterface) {
            throw new InvalidArgumentException();
        }

        $this->counters = $counters;

        foreach ($this->queue as $call) {
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $call[TWO]->register($this->counters->{$call[ZERO]}(...$call[ONE])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
