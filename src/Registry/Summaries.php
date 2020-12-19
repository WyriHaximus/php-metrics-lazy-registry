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

use const WyriHaximus\Constants\Numeric\ONE;
use const WyriHaximus\Constants\Numeric\TWO;
use const WyriHaximus\Constants\Numeric\ZERO;

final class Summaries implements SummariesInterface
{
    private ?SummariesInterface $summaries = null;

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

    public function summary(Label ...$labels): SummaryInterface
    {
        Label\Utils::validate($this->requiredLabelNames, ...$labels);

        if ($this->summaries instanceof SummariesInterface) {
            return $this->summaries->summary(...$labels);
        }

        $ghost         = new Summary($this->name, $this->description, ...$labels);
        $this->queue[] = [__FUNCTION__, func_get_args(), $ghost];

        return $ghost;
    }

    /**
     * @return iterable<SummaryInterface>
     */
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
            /** @psalm-suppress PossiblyInvalidMethodCall */
            $call[TWO]->register($this->summaries->{$call[ZERO]}(...$call[ONE])); /** @phpstan-ignore-line */
        }

        $this->queue = [];
    }
}
