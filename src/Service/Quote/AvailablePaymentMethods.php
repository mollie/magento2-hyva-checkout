<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Service\Quote;

use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;

class AvailablePaymentMethods implements AvailablePaymentMethodsInterface
{
    public function __construct(
        private readonly MethodList $methodList
    ) {
    }

    public function getCodes(CartInterface $quote): array
    {
        return array_values(array_map(
            static fn (MethodInterface $method): string => $method->getCode(),
            $this->methodList->getAvailableMethods($quote)
        ));
    }
}
