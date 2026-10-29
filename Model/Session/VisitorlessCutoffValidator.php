<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Model\Session;

use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\ResourceModel\Visitor as VisitorResource;
use Magento\Framework\Exception\SessionException;
use Magento\Framework\Phrase;
use Magento\Framework\Session\Generic;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Session\ValidatorInterface;

/**
 * Enforces the password-change session cutoff for sessions without a customer_visitor row.
 *
 * Core CutoffValidator reads the login time from customer_visitor and skips sessions whose row is missing
 * (never written, or cleaned by visitor_clean); this takes it from the visitor session data instead.
 * Not gated by the toggle: sessions opened while tracking was off must stay covered after it is re-enabled.
 */
class VisitorlessCutoffValidator implements ValidatorInterface
{
    /**
     * @param CustomerResource $customerResource
     * @param VisitorResource $visitorResource
     * @param Generic $visitorSession
     */
    public function __construct(
        private readonly CustomerResource $customerResource,
        private readonly VisitorResource $visitorResource,
        private readonly Generic $visitorSession
    ) {
    }

    /**
     * Destroy the session when it was opened before the customer's session cutoff.
     *
     * @param SessionManagerInterface $session
     * @return void
     * @throws SessionException
     */
    public function validate(SessionManagerInterface $session): void
    {
        $visitor = $this->visitorSession->getVisitorData();
        if (!is_array($visitor)
            || empty($visitor['customer_id'])
            || empty($visitor['created_at'])
            || (!empty($visitor['visitor_id'])
                && $this->visitorResource->fetchCreatedAt((int)$visitor['visitor_id']) !== null)
        ) {
            return;
        }

        $cutoff = $this->customerResource->findSessionCutOff((int)$visitor['customer_id']);
        $createdAt = strtotime((string)$visitor['created_at']);
        if ($cutoff !== null && $createdAt !== false && $cutoff > $createdAt) {
            $session->destroy(['clear_storage' => false]);
            throw new SessionException(new Phrase('The session has expired, please login again.'));
        }
    }
}
