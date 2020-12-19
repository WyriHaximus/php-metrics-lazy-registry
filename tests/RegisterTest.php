<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\Metrics\LazyRegistry;

use InvalidArgumentException;
use WyriHaximus\Metrics\Counter as CounterInterface;
use WyriHaximus\Metrics\Gauge as GaugeInterface;
use WyriHaximus\Metrics\Histogram as HistogramInterface;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\LazyRegistry\Counter;
use WyriHaximus\Metrics\LazyRegistry\Gauge;
use WyriHaximus\Metrics\LazyRegistry\Histogram;
use WyriHaximus\Metrics\LazyRegistry\Registry;
use WyriHaximus\Metrics\LazyRegistry\Registry\Counters;
use WyriHaximus\Metrics\LazyRegistry\Registry\Gauges;
use WyriHaximus\Metrics\LazyRegistry\Registry\Histograms;
use WyriHaximus\Metrics\LazyRegistry\Registry\Summaries;
use WyriHaximus\Metrics\LazyRegistry\Summary;
use WyriHaximus\Metrics\Registry as RegistryInterface;
use WyriHaximus\Metrics\Registry\Counters as CountersInterface;
use WyriHaximus\Metrics\Registry\Gauges as GaugesInterface;
use WyriHaximus\Metrics\Registry\Histograms as HistogramsInterface;
use WyriHaximus\Metrics\Registry\Summaries as SummariesInterface;
use WyriHaximus\Metrics\Summary as SummaryInterface;
use WyriHaximus\TestUtilities\TestCase;

final class RegisterTest extends TestCase
{
    /**
     * @param class-string $mock
     *
     * @test
     * @dataProvider provideObjectAndMockPairs
     */
    public function register(object $object, string $mock): void
    {
        self::expectException(InvalidArgumentException::class);

        $mock = $this->prophesize($mock)->reveal();

        $object->register($mock); /** @phpstan-ignore-line */
        $object->register($mock); /** @phpstan-ignore-line */
    }

    /**
     * @return iterable<array<object|class-string>>
     */
    public function provideObjectAndMockPairs(): iterable
    {
        $name        = 'name';
        $description = 'description';
        $buckets     = new HistogramInterface\Buckets(0.1);
        $labels      = [new Label('label', 'label')];
        $labelNames  = [new Label\Name('label')];

        yield [
            new Counter($name, $description, ...$labels),
            CounterInterface::class,
        ];

        yield [
            new Gauge($name, $description, ...$labels),
            GaugeInterface::class,
        ];

        yield [
            new Histogram($name, $description, $buckets, ...$labels),
            HistogramInterface::class,
        ];

        yield [
            new Registry(),
            RegistryInterface::class,
        ];

        yield [
            new Summary($name, $description, ...$labels),
            SummaryInterface::class,
        ];

        yield [
            new Counters($name, $description, ...$labelNames),
            CountersInterface::class,
        ];

        yield [
            new Gauges($name, $description, ...$labelNames),
            GaugesInterface::class,
        ];

        yield [
            new Histograms($name, $description, $buckets, ...$labelNames),
            HistogramsInterface::class,
        ];

        yield [
            new Summaries($name, $description, ...$labelNames),
            SummariesInterface::class,
        ];
    }
}
