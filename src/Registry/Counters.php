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

final class Counters implements CountersInterface
{
    private CountersInterface|null $counters = null;
    /** @var array<string> */
    private readonly array $requiredLabelNames;

    /** @var array<array{function: string, args: array<mixed>, ghost: Counter}> */
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

    public function counter(Label ...$labels): CounterInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->counters instanceof CountersInterface) {
            return $this->counters->counter(...$labels);
        }

        $ghost         = new Counter($this->name, $this->description, ...$labels);
        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args(), 'ghost' => $ghost];

        return $ghost;
    }

    /** @return iterable<CounterInterface> */
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
            /** @psalm-suppress MixedArgument */
            $call['ghost']->register($this->counters->{$call['function']}(...$call['args'])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
