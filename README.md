# Lazy Metrics Registry

![Continuous Integration](https://github.com/wyrihaximus/php-metrics-lazy-registry/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/wyrihaximus/metrics-lazy-registry/v/stable.png)](https://packagist.org/packages/wyrihaximus/metrics-lazy-registry)
[![Total Downloads](https://poser.pugx.org/wyrihaximus/metrics-lazy-registry/downloads.png)](https://packagist.org/packages/wyrihaximus/metrics-lazy-registry/stats)
[![Code Coverage](https://coveralls.io/repos/github/WyriHaximus/php-metrics-lazy-registry/badge.svg?branchmaster)](https://coveralls.io/github/WyriHaximus/php-metrics-lazy-registry?branch=master)
[![Type Coverage](https://shepherd.dev/github/WyriHaximus/php-metrics-lazy-registry/coverage.svg)](https://shepherd.dev/github/WyriHaximus/php-metrics-lazy-registry)
[![License](https://poser.pugx.org/wyrihaximus/metrics-lazy-registry/license.png)](https://packagist.org/packages/wyrihaximus/metrics-lazy-registry)

# Installation

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/metrics-lazy-registry
```

# Usage

Mainly designed to hold calls until the real registry is available:

```php
<?php

use WyriHaximus\Metrics\Configuration as MetricsConfiguration;
use WyriHaximus\Metrics\InMemory\Registry as InMemoryRegistry;
use WyriHaximus\Metrics\LazyRegistry\Registry as LazyRegistry;
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$registry      = new LazyRegistry();
// Wait some time before the real registry is available
$registry->register(new InMemoryRegistry(MetricsConfiguration::create()));
```

# License

The MIT License (MIT)

Copyright (c) 2020 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
