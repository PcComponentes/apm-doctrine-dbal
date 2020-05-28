# Elastic APM for Doctrine DBAL

This library supports Span traces of SQL queries using Doctrine DBAL.

## Installation

1) Install via [composer](https://getcomposer.org/)

    ```shell script
    composer require pccomponentes/apm-doctrine-dbal
    ```

## Usage

In all cases, an already created instance of [ElasticApmTracer](https://github.com/zoilomora/elastic-apm-agent-php) is assumed.

### Native PHP

```php
<?php
declare(strict_types=1);

$sqlLogger = new PcComponentes\ElasticAPM\Doctrine\DBAL\Logging\SQLLogger(
    $apmTracer, /** \ZoiloMora\ElasticAPM\ElasticApmTracer instance. */
    'test',
    'mysql',
);

$configuration = new Doctrine\DBAL\Configuration();
$configuration->setSQLLogger($sqlLogger);

$connection = Doctrine\DBAL\DriverManager::getConnection(
    [
        'url' => 'mysql://user:password@localhost:3306/test',
        'driver' => 'pdo_mysql',
        'charset' => 'UTF8',
    ],
    $configuration,
);

/** ... Use the connection in your project */
```

### Service Container (Symfony)

```yaml
connection.dbal.test:
  class: Doctrine\DBAL\Connection
  factory: 'Doctrine\DBAL\DriverManager::getConnection'
  arguments:
    $params:
      url: 'mysql://user:password@localhost:3306/test'
      driver: pdo_mysql
      charset: UTF8
    $config: '@connection.dbal.test.configuration'

connection.dbal.test.configuration:
  class: Doctrine\DBAL\Configuration
  calls:
    - method: setSQLLogger
      arguments:
        - '@apm.dbal.test'

apm.dbal.test:
  class: Pcc\ApiLegacyBridge\Util\ElasticAPM\DBAL\SQLLogger
  arguments:
    $elasticApmTracer: '@apm.tracer' # \ZoiloMora\ElasticAPM\ElasticApmTracer instance.
    $instance: 'test'
    $engine: 'mysql'
```

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT)

Read [LICENSE](LICENSE) for more information
