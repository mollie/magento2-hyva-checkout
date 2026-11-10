<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Service\Order;

/**
 * Raised while \Mollie\Payment\Service\Order\Reorder recreates an order in this request.
 */
class ReorderInProgress
{
    private int $depth = 0;

    public function start(): void
    {
        $this->depth++;
    }

    public function stop(): void
    {
        $this->depth = max(0, $this->depth - 1);
    }

    public function isActive(): bool
    {
        return $this->depth > 0;
    }
}
