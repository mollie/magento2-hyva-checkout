<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Service\Quote;

use Magento\Quote\Api\Data\CartInterface;

interface AvailablePaymentMethodsInterface
{
    /**
     * @return list<string>
     */
    public function getCodes(CartInterface $quote): array;
}
