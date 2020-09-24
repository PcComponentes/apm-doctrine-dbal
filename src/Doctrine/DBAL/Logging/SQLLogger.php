<?php
declare(strict_types=1);

namespace PcComponentes\ElasticAPM\Doctrine\DBAL\Logging;

use Aaronidas\SQLLexer\SQL\Signature;
use Doctrine\DBAL\Logging\SQLLogger as SQLLoggerBase;
use ZoiloMora\ElasticAPM\ElasticApmTracer;
use ZoiloMora\ElasticAPM\Events\Span\Context;
use ZoiloMora\ElasticAPM\Events\Span\Span;

final class SQLLogger implements SQLLoggerBase
{
    private const EXCLUDED_QUERIES = [
        '"START TRANSACTION"',
        '"COMMIT"',
        '"RELEASE SAVEPOINT"',
        '"ROLLBACK"',
        '"ROLLBACK TO SAVEPOINT"',
    ];

    private const SPAN_NAME = 'DB Query';
    private const SPAN_TYPE = 'DB';
    private const SPAN_ACTION = 'query';
    private const CONTEXT_DB_TYPE = 'sql';
    private const STACKTRACE_SKIP = 3;

    private ElasticApmTracer $elasticApmTracer;
    private string $instance;
    private string $engine;
    private bool $debugMode;

    private ?Span $span;

    public function __construct(
        ElasticApmTracer $elasticApmTracer,
        string $instance,
        string $engine,
        bool $debugMode = false
    ) {
        $this->elasticApmTracer = $elasticApmTracer;
        $this->instance = $instance;
        $this->engine = $engine;
        $this->debugMode = $debugMode;
        $this->span = null;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        if (false === $this->elasticApmTracer->active()) {
            return;
        }

        if (false === $this->debugMode && true === \in_array($sql, self::EXCLUDED_QUERIES, true)) {
            $this->span = null;

            return;
        }

        $spanName = Signature::parse($sql);

        try {
            $this->span = $this->elasticApmTracer->startSpan(
                '' !== $spanName ? $spanName : self::SPAN_NAME,
                self::SPAN_TYPE,
                $this->engine,
                self::SPAN_ACTION,
                $this->getContext($sql, $params, $types),
                self::STACKTRACE_SKIP,
            );
        } catch (\Throwable $exception) {
            // Nothing
        }
    }

    public function stopQuery()
    {
        if (null === $this->span) {
            return;
        }

        $this->span->stop();
    }

    private function getContext($sql, ?array $params, ?array $types): Context
    {
        return Context::fromDb(
            new Context\Db(
                $this->instance,
                null,
                $sql,
                self::CONTEXT_DB_TYPE,
            ),
        );
    }
}
