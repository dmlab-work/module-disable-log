<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Plugin\Customer;

use DmLab\DisableLog\Model\Config;
use Magento\Customer\Model\ResourceModel\Visitor;
use Magento\Framework\Model\AbstractModel;

/**
 * Skips the customer_visitor write when the visitor-log toggle is on.
 */
class SkipVisitorLog
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Return without writing when online-visitor tracking is disabled.
     *
     * @param Visitor $subject
     * @param callable $proceed
     * @param AbstractModel $object
     * @return Visitor
     */
    public function aroundSave(Visitor $subject, callable $proceed, AbstractModel $object): Visitor
    {
        if ($this->config->isVisitorLogDisabled()) {
            return $subject;
        }

        return $proceed($object);
    }
}
