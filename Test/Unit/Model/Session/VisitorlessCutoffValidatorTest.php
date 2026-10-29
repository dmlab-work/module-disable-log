<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\DisableLog\Test\Unit\Model\Session;

use DmLab\DisableLog\Model\Session\VisitorlessCutoffValidator;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\ResourceModel\Visitor as VisitorResource;
use Magento\Framework\Exception\SessionException;
use Magento\Framework\Session\Generic;
use Magento\Framework\Session\SessionManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VisitorlessCutoffValidatorTest extends TestCase
{
    private const LOGIN_AT = '2026-10-01 10:00:00';
    private const EXISTING_VISITOR_ID = 3;
    private const CLEANED_VISITOR_ID = 4;

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function expiredSessions(): array
    {
        return [
            'no visitor_id' => [['customer_id' => 7, 'created_at' => self::LOGIN_AT]],
            'visitor row cleaned' => [
                ['visitor_id' => self::CLEANED_VISITOR_ID, 'customer_id' => 7, 'created_at' => self::LOGIN_AT],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $visitorData
     */
    #[DataProvider('expiredSessions')]
    public function testDestroysSessionOpenedBeforeCutoff(array $visitorData): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->expects(self::once())->method('destroy')->with(['clear_storage' => false]);

        $this->expectException(SessionException::class);
        $this->validator($visitorData, strtotime(self::LOGIN_AT) + 1, 7)->validate($session);
    }

    /**
     * @return array<string, array{mixed, ?int}>
     */
    public static function keptSessions(): array
    {
        $cutoff = strtotime(self::LOGIN_AT) + 1;
        return [
            'no visitor data' => [null, $cutoff],
            'guest' => [['created_at' => self::LOGIN_AT], $cutoff],
            'core validator owns existing visitor row' => [
                ['visitor_id' => self::EXISTING_VISITOR_ID, 'customer_id' => 7, 'created_at' => self::LOGIN_AT],
                $cutoff,
            ],
            'no login time' => [['customer_id' => 7], $cutoff],
            'no cutoff' => [['customer_id' => 7, 'created_at' => self::LOGIN_AT], null],
            'login at cutoff' => [['customer_id' => 7, 'created_at' => self::LOGIN_AT], $cutoff - 1],
            'login after cutoff' => [['customer_id' => 7, 'created_at' => self::LOGIN_AT], $cutoff - 2],
            'unparsable login time' => [['customer_id' => 7, 'created_at' => 'garbage'], $cutoff],
        ];
    }

    #[DataProvider('keptSessions')]
    public function testKeepsSession(mixed $visitorData, ?int $cutoff): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->expects(self::never())->method('destroy');

        $this->validator($visitorData, $cutoff, 7)->validate($session);
    }

    public function testDiAddsValidatorToCustomerSessionValidators(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/di.xml');
        self::assertNotFalse($xml);

        $items = $xml->xpath(
            "/config/virtualType[@name='SessionValidator']/arguments/argument[@name='validators']/item"
        );

        self::assertCount(1, $items);
        self::assertSame(VisitorlessCutoffValidator::class, trim((string)$items[0]));
    }

    private function validator(mixed $visitorData, ?int $cutoff, int $customerId): VisitorlessCutoffValidator
    {
        $visitorSession = self::createStub(Generic::class);
        $visitorSession->method('__call')->willReturnCallback(
            static fn (string $method): mixed => $method === 'getVisitorData' ? $visitorData : null
        );

        $customerResource = self::createStub(CustomerResource::class);
        $customerResource->method('findSessionCutOff')->willReturnCallback(
            static fn (int $id): ?int => $id === $customerId ? $cutoff : null
        );

        $visitorResource = self::createStub(VisitorResource::class);
        $visitorResource->method('fetchCreatedAt')->willReturnCallback(
            static fn (int $id): ?int => $id === self::EXISTING_VISITOR_ID ? strtotime(self::LOGIN_AT) : null
        );

        return new VisitorlessCutoffValidator($customerResource, $visitorResource, $visitorSession);
    }
}
