<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Plugin\Reports;

use DmLab\DisableLog\Model\Config;
use Magento\Framework\Model\AbstractModel;
use Magento\Reports\Model\ResourceModel\Product\Index\AbstractIndex;

/**
 * Skips viewed/compared product-index rows with neither visitor_id nor customer_id while visitor tracking is off:
 * nothing can read them and index cleanup only removes rows with visitor_id > 0.
 */
class SkipAnonymousProductIndex
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Return without writing an unowned guest row when online-visitor tracking is disabled.
     *
     * @param AbstractIndex $subject
     * @param callable $proceed
     * @param AbstractModel $object
     * @return AbstractIndex
     */
    public function aroundSave(AbstractIndex $subject, callable $proceed, AbstractModel $object): AbstractIndex
    {
        if ($this->config->isVisitorLogDisabled() && !$object->getVisitorId() && !$object->getCustomerId()) {
            return $subject;
        }

        return $proceed($object);
    }
}
