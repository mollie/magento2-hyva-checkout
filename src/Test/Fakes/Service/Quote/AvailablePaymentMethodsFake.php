<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Test\Fakes\Service\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\HyvaCheckout\Service\Quote\AvailablePaymentMethodsInterface;

class AvailablePaymentMethodsFake implements AvailablePaymentMethodsInterface
{
    /**
     * @var list<string>
     */
    private array $codes = [];
    private int $numberOfLookups = 0;

    public function withCodes(string ...$codes): self
    {
        $fake = clone $this;
        $fake->codes = array_values($codes);

        return $fake;
    }

    public function getCodes(CartInterface $quote): array
    {
        $this->numberOfLookups++;

        return $this->codes;
    }

    public function getNumberOfLookups(): int
    {
        return $this->numberOfLookups;
    }
}
