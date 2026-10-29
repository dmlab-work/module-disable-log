<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Plugin;

use DmLab\DisableLog\Plugin\Customer\SkipCustomerLog;
use DmLab\DisableLog\Plugin\Customer\SkipVisitorLog;
use DmLab\DisableLog\Plugin\Reports\SkipAnonymousProductIndex;
use DmLab\DisableLog\Plugin\Search\SkipSearchLog;
use Magento\Customer\Model\Logger;
use Magento\Customer\Model\ResourceModel\Visitor;
use Magento\Reports\Model\ResourceModel\Product\Index\AbstractIndex;
use Magento\Search\Model\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Magento binds around<X> to subject method x by name only; a mismatch silently disables the plugin.
 */
class PluginSignatureTest extends TestCase
{
    /**
     * @return array<string, array{class-string, class-string}>
     */
    public static function pluginProvider(): array
    {
        return [
            'customer log' => [SkipCustomerLog::class, Logger::class],
            'visitor log' => [SkipVisitorLog::class, Visitor::class],
            'anonymous product index' => [SkipAnonymousProductIndex::class, AbstractIndex::class],
            'search log' => [SkipSearchLog::class, Query::class],
        ];
    }

    /**
     * @param class-string $plugin
     * @param class-string $subject
     */
    #[DataProvider('pluginProvider')]
    public function testAroundMethodsMatchSubjectMethods(string $plugin, string $subject): void
    {
        $aroundMethods = array_filter(
            (new \ReflectionClass($plugin))->getMethods(\ReflectionMethod::IS_PUBLIC),
            static fn (\ReflectionMethod $m): bool => str_starts_with($m->getName(), 'around')
        );
        self::assertNotEmpty($aroundMethods);

        foreach ($aroundMethods as $around) {
            $name = lcfirst(substr($around->getName(), 6));
            self::assertTrue(method_exists($subject, $name), "$subject::$name() does not exist");

            $target = new \ReflectionMethod($subject, $name);
            self::assertTrue($target->isPublic(), "$subject::$name() is not public");

            $expected = array_map(
                static fn (\ReflectionParameter $p): string => $p->getName(),
                $target->getParameters()
            );
            $actual = array_map(
                static fn (\ReflectionParameter $p): string => $p->getName(),
                array_slice($around->getParameters(), 2)
            );
            self::assertSame($expected, $actual, "{$around->getName()} arguments differ from $subject::$name()");
        }
    }
}
