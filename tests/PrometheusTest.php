<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\Metrics\LazyRegistry;

use WyriHaximus\Metrics\Configuration;
use WyriHaximus\Metrics\Factory;
use WyriHaximus\Metrics\InMemory\Registry as InMemoryRegistry;
use WyriHaximus\Metrics\Label;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\LazyRegistry\Registry as LazyRegistry;
use WyriHaximus\Metrics\Printer\Prometheus;
use WyriHaximus\TestUtilities\TestCase;

use function array_reverse;
use function range;
use function Safe\file_get_contents;

use const DIRECTORY_SEPARATOR;

final class PrometheusTest extends TestCase
{
    /**
     * @test
     */
    public function print(): void
    {
        $registry = new LazyRegistry();
        $registry->counter('counter', '', new Name('label'), new Name('class'))->counter(new Label('label', 'label'), new Label('class', self::class))->incr();
        $registry->counter('counter', '', new Name('label'), new Name('class'))->counter(new Label('label', 'labol'), new Label('class', self::class))->incrTo(1);
        $registry->counter('counter', '', new Name('label'), new Name('class'))->counter(new Label('label', 'labol'), new Label('class', self::class))->incrBy(132);
        $registry->counter('cuonter', 'simple counter counting things')->counter()->incr();
        $registry->counter('cuonter', 'simple counter counting things')->counter()->incrBy(133);
        $registry->gauge('gauge', '', new Name('label'))->gauge(new Label('label', 'label'))->incr();
        $registry->gauge('gauge', '', new Name('label'))->gauge(new Label('label', 'label'))->dcr();
        $registry->gauge('gauge', '', new Name('label'))->gauge(new Label('label', 'label'))->incrBy(1);
        $registry->gauge('gauge', '', new Name('label'))->gauge(new Label('label', 'labol'))->dcrBy(1);
        $registry->gauge('gauge', '', new Name('label'))->gauge(new Label('label', 'labol'))->incrBy(301);
        $registry->gauge('guage', 'simple gauge gauging things')->gauge()->incr();
        $registry->gauge('guage', 'simple gauge gauging things')->gauge()->incrBy(300);
        $registry->histogram('histogram', '', Factory::defaultBuckets(), new Name('label'))->histogram(new Label('label', 'label'))->observe(0.6);
        $registry->histogram('histogram', '', Factory::defaultBuckets(), new Name('label'))->histogram(new Label('label', 'label'))->observe(3.3);
        $registry->histogram('hostigram', 'simple histogram histogramming things', Factory::defaultBuckets())->histogram()->observe(0.6);
        $registry->histogram('hostigram', 'simple histogram histogramming things', Factory::defaultBuckets())->histogram()->observe(3.3);
        $registry->summary('summary', '', Factory::defaultQuantiles(), new Name('label'))->summary(new Label('label', 'label'))->observe(0.6);
        $registry->summary('summary', '', Factory::defaultQuantiles(), new Name('label'))->summary(new Label('label', 'label'))->observe(3.3);
        $registry->summary('sammury', 'simple summary sammury things', Factory::defaultQuantiles())->summary()->observe(0.6);
        $registry->summary('sammury', 'simple summary sammury things', Factory::defaultQuantiles())->summary()->observe(3.3);
        foreach (array_reverse(range(1, 100)) as $i) {
            $registry->summary('summary', 'bla bla bla', Factory::defaultQuantiles(), new Name('label'))->summary(new Label('label', 'value'))->observe($i);
        }

        $registry->register(new InMemoryRegistry(Configuration::create()));

        self::assertSame(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'prometheus.txt'), $registry->print(new Prometheus()));
    }
}
