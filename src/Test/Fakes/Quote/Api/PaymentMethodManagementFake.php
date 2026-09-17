<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Test\Fakes\Quote\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;

class PaymentMethodManagementFake implements PaymentMethodManagementInterface
{
    /**
     * @var list<string|null>
     */
    private array $methodsThatWereSet = [];
    private bool $shouldFailOnSet = false;

    public function givenSetFails(): void
    {
        $this->shouldFailOnSet = true;
    }

    public function set($cartId, PaymentInterface $method): int
    {
        if ($this->shouldFailOnSet) {
            throw new LocalizedException(__('The requested Payment Method is not available.'));
        }

        $this->methodsThatWereSet[] = $method->getMethod();

        return count($this->methodsThatWereSet);
    }

    public function get($cartId): ?PaymentInterface
    {
        return null;
    }

    public function getList($cartId): array
    {
        return [];
    }

    /**
     * @return list<string|null>
     */
    public function getMethodsThatWereSet(): array
    {
        return $this->methodsThatWereSet;
    }
}
