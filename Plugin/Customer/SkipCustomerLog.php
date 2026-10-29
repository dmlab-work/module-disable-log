<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Plugin\Customer;

use DmLab\DisableLog\Model\Config;
use Magento\Customer\Model\Logger;

/**
 * Skips the customer_log login/logout write when the customer-log toggle is on.
 */
class SkipCustomerLog
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Return without writing when customer logging is disabled.
     *
     * @param Logger $subject
     * @param callable $proceed
     * @param int $customerId
     * @param array $data
     * @return Logger
     */
    public function aroundLog(Logger $subject, callable $proceed, $customerId, array $data): Logger
    {
        if ($this->config->isCustomerLogDisabled()) {
            return $subject;
        }

        return $proceed($customerId, $data);
    }
}
