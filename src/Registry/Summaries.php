<?php

declare(strict_types=1);

namespace WyriHaximus\Metrics\LazyRegistry\Registry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\LazyRegistry\Summary;
use WyriHaximus\Metrics\Registry\Summaries as SummariesInterface;
use WyriHaximus\Metrics\Summary as SummaryInterface;

use function array_map;
use function func_get_args;

final class Summaries implements SummariesInterface
{
    private SummariesInterface|null $summaries = null;

    /** @var array<string> */
    private readonly array $requiredLabelNames;

    /** @var array<array{function: string, args: array<mixed>, ghost: Summary}> */
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

    public function summary(Label ...$labels): SummaryInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->summaries instanceof SummariesInterface) {
            return $this->summaries->summary(...$labels);
        }

        $ghost         = new Summary($this->name, $this->description, ...$labels);
        $this->queue[] = ['function' => __FUNCTION__, 'args' => func_get_args(), 'ghost' => $ghost];

        return $ghost;
    }

    /** @return iterable<SummaryInterface> */
    public function summaries(): iterable
    {
        if ($this->summaries instanceof SummariesInterface) {
            yield from $this->summaries->summaries();
        }

        yield from [];
    }

    public function register(SummariesInterface $summaries): void
    {
        if ($this->summaries instanceof SummariesInterface) {
            throw new InvalidArgumentException();
        }

        $this->summaries = $summaries;

        foreach ($this->queue as $call) {
            /** @psalm-suppress MixedArgument */
            $call['ghost']->register($this->summaries->{$call['function']}(...$call['args'])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
