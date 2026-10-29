<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Plugin;

use DmLab\DisableLog\Model\Config;
use DmLab\DisableLog\Plugin\Customer\SkipCustomerLog;
use DmLab\DisableLog\Plugin\Customer\SkipVisitorLog;
use DmLab\DisableLog\Plugin\Reports\SkipAnonymousProductIndex;
use DmLab\DisableLog\Plugin\Search\SkipSearchLog;
use Magento\Customer\Model\Logger;
use Magento\Customer\Model\ResourceModel\Visitor;
use Magento\Customer\Model\Visitor as VisitorModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Reports\Model\Product\Index\Viewed;
use Magento\Reports\Model\ResourceModel\Product\Index\AbstractIndex;
use Magento\Search\Model\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * All plugins over the real Config reader: each toggle stops only its own writes.
 */
class ToggleIsolationTest extends TestCase
{
    /** @var array<string, bool> */
    private array $flags = [];

    /**
     * @return iterable<string, array{bool, bool, bool}>
     */
    public static function toggleCombinations(): iterable
    {
        foreach ([false, true] as $customer) {
            foreach ([false, true] as $visitor) {
                foreach ([false, true] as $search) {
                    $name = sprintf('customer=%d visitor=%d search=%d', $customer, $visitor, $search);
                    yield $name => [$customer, $visitor, $search];
                }
            }
        }
    }

    #[DataProvider('toggleCombinations')]
    public function testEachToggleStopsOnlyItsOwnWrites(bool $customer, bool $visitor, bool $search): void
    {
        $this->flags = [
            Config::XML_PATH_DISABLE_CUSTOMER_LOG => $customer,
            Config::XML_PATH_DISABLE_VISITOR_LOG => $visitor,
            Config::XML_PATH_DISABLE_SEARCH_LOG => $search,
        ];

        self::assertSame(
            [
                'customer_log' => !$customer,
                'customer_visitor' => !$visitor,
                'report_viewed_product_index.guest' => !$visitor,
                'search_query.popularity' => !$search,
                'search_query.num_results' => !$search,
            ],
            $this->runAllWriters()
        );
    }

    public function testReEnablingLoggingRestoresWrites(): void
    {
        $this->flags = [
            Config::XML_PATH_DISABLE_CUSTOMER_LOG => true,
            Config::XML_PATH_DISABLE_VISITOR_LOG => true,
            Config::XML_PATH_DISABLE_SEARCH_LOG => true,
        ];
        self::assertSame([false], array_unique(array_values($this->runAllWriters())));

        $this->flags = array_map(static fn (): bool => false, $this->flags);
        self::assertSame([true], array_unique(array_values($this->runAllWriters())));
    }

    /**
     * Invoke every guarded writer once; report which ones reached core.
     *
     * @return array<string, bool>
     */
    private function runAllWriters(): array
    {
        $scopeConfig = self::createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            fn (string $path): bool => $this->flags[$path] ?? false
        );
        $config = new Config($scopeConfig);

        $wrote = [];
        $proceed = static function (string $key, object $result) use (&$wrote): callable {
            $wrote[$key] = false;
            return static function () use (&$wrote, $key, $result): object {
                $wrote[$key] = true;
                return $result;
            };
        };

        $logger = self::createStub(Logger::class);
        (new SkipCustomerLog($config))->aroundLog($logger, $proceed('customer_log', $logger), 1, []);

        $visitor = self::createStub(Visitor::class);
        (new SkipVisitorLog($config))->aroundSave(
            $visitor,
            $proceed('customer_visitor', $visitor),
            self::createStub(VisitorModel::class)
        );

        $productIndex = self::createStub(AbstractIndex::class);
        (new SkipAnonymousProductIndex($config))->aroundSave(
            $productIndex,
            $proceed('report_viewed_product_index.guest', $productIndex),
            self::createStub(Viewed::class)
        );

        $query = self::createStub(Query::class);
        $searchPlugin = new SkipSearchLog($config);
        $searchPlugin->aroundSaveIncrementalPopularity($query, $proceed('search_query.popularity', $query));
        $searchPlugin->aroundSaveNumResults($query, $proceed('search_query.num_results', $query), 3);

        return $wrote;
    }
}
