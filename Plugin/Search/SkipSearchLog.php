<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Plugin\Search;

use DmLab\DisableLog\Model\Config;
use Magento\Search\Model\Query;

/**
 * Skips the search_query statistics writes when the search-log toggle is on.
 */
class SkipSearchLog
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Return without bumping popularity when search-query logging is disabled.
     *
     * @param Query $subject
     * @param callable $proceed
     * @return Query
     */
    public function aroundSaveIncrementalPopularity(Query $subject, callable $proceed): Query
    {
        if ($this->config->isSearchLogDisabled()) {
            return $subject;
        }

        return $proceed();
    }

    /**
     * Return without persisting the result count when search-query logging is disabled.
     *
     * The count is still set on the model so in-request readers see the same state as stock Magento.
     *
     * @param Query $subject
     * @param callable $proceed
     * @param int $numResults
     * @return Query
     */
    public function aroundSaveNumResults(Query $subject, callable $proceed, $numResults): Query
    {
        if ($this->config->isSearchLogDisabled()) {
            $subject->setData('num_results', $numResults);
            return $subject;
        }

        return $proceed($numResults);
    }
}
