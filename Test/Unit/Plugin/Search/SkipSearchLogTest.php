<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Plugin\Search;

use DmLab\DisableLog\Model\Config;
use DmLab\DisableLog\Plugin\Search\SkipSearchLog;
use Magento\Search\Model\Query;
use PHPUnit\Framework\TestCase;

class SkipSearchLogTest extends TestCase
{
    public function testSkipsPopularityWriteWhenDisabled(): void
    {
        $subject = self::createStub(Query::class);
        $plugin = new SkipSearchLog($this->config(true));

        $result = $plugin->aroundSaveIncrementalPopularity(
            $subject,
            static function (): never {
                self::fail('search_query popularity write must be skipped');
            }
        );

        self::assertSame($subject, $result);
    }

    public function testWritesPopularityWhenEnabled(): void
    {
        $proceedResult = self::createStub(Query::class);
        $calls = 0;
        $plugin = new SkipSearchLog($this->config(false));

        $result = $plugin->aroundSaveIncrementalPopularity(
            self::createStub(Query::class),
            static function () use (&$calls, $proceedResult): Query {
                $calls++;
                return $proceedResult;
            }
        );

        self::assertSame(1, $calls);
        self::assertSame($proceedResult, $result);
    }

    public function testSkipsNumResultsWriteWhenDisabledButKeepsModelState(): void
    {
        $subject = (new \ReflectionClass(Query::class))->newInstanceWithoutConstructor();
        $plugin = new SkipSearchLog($this->config(true));

        $result = $plugin->aroundSaveNumResults(
            $subject,
            static function (): never {
                self::fail('search_query num_results write must be skipped');
            },
            42
        );

        self::assertSame($subject, $result);
        self::assertSame(42, $subject->getNumResults());
    }

    public function testWritesNumResultsWhenEnabled(): void
    {
        $proceedResult = self::createStub(Query::class);
        $calls = [];
        $plugin = new SkipSearchLog($this->config(false));

        $result = $plugin->aroundSaveNumResults(
            self::createStub(Query::class),
            static function (int $numResults) use (&$calls, $proceedResult): Query {
                $calls[] = $numResults;
                return $proceedResult;
            },
            7
        );

        self::assertSame([7], $calls);
        self::assertSame($proceedResult, $result);
    }

    public function testPropagatesCoreExceptionWhenEnabled(): void
    {
        $plugin = new SkipSearchLog($this->config(false));

        $this->expectException(\RuntimeException::class);
        $plugin->aroundSaveIncrementalPopularity(
            self::createStub(Query::class),
            static function (): never {
                throw new \RuntimeException('DB write failed');
            }
        );
    }

    public function testPropagatesNumResultsCoreExceptionWhenEnabled(): void
    {
        $plugin = new SkipSearchLog($this->config(false));

        $this->expectException(\RuntimeException::class);
        $plugin->aroundSaveNumResults(
            self::createStub(Query::class),
            static function (): never {
                throw new \RuntimeException('DB write failed');
            },
            7
        );
    }

    public function testDiRegistersPluginGlobally(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/di.xml');
        self::assertNotFalse($xml);

        $nodes = $xml->xpath("/config/type[@name='Magento\\Search\\Model\\Query']/plugin");

        self::assertCount(1, $nodes);
        self::assertSame(SkipSearchLog::class, (string)$nodes[0]['type']);
    }

    private function config(bool $disabled): Config
    {
        $config = self::createStub(Config::class);
        $config->method('isSearchLogDisabled')->willReturn($disabled);
        return $config;
    }
}
