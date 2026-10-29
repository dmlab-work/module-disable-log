<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Typed reader over the per-source toggles. Read at default scope: the suppressed writers run without store context.
 */
class Config
{
    public const XML_PATH_DISABLE_CUSTOMER_LOG = 'dmlab_disable_log/general/disable_customer_log';
    public const XML_PATH_DISABLE_VISITOR_LOG = 'dmlab_disable_log/general/disable_visitor_log';
    public const XML_PATH_DISABLE_SEARCH_LOG = 'dmlab_disable_log/general/disable_search_log';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Whether customer login/logout logging (customer_log) is suppressed.
     *
     * @return bool
     */
    public function isCustomerLogDisabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DISABLE_CUSTOMER_LOG);
    }

    /**
     * Whether online-visitor tracking (customer_visitor) is suppressed.
     *
     * @return bool
     */
    public function isVisitorLogDisabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DISABLE_VISITOR_LOG);
    }

    /**
     * Whether search-query statistics (search_query) are suppressed.
     *
     * @return bool
     */
    public function isSearchLogDisabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DISABLE_SEARCH_LOG);
    }
}
