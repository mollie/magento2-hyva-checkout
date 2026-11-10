<?php

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin\Mollie\Service\Order;

use Closure;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\HyvaCheckout\Service\Order\ReorderInProgress;
use Mollie\Payment\Service\Order\Reorder;

class MarkReorderInProgressPlugin
{
    private ReorderInProgress $reorderInProgress;

    public function __construct(
        ReorderInProgress $reorderInProgress,
    ) {
        $this->reorderInProgress = $reorderInProgress;
    }

    /**
     * The Second Chance email link recreates the order on the storefront, so let the Hyvä Checkout
     * quote validation know this placement does not come from the checkout.
     */
    public function aroundCreate(Reorder $subject, Closure $proceed, OrderInterface $originalOrder): OrderInterface
    {
        $this->reorderInProgress->start();
        try {
            return $proceed($originalOrder);
        } finally {
            $this->reorderInProgress->stop();
        }
    }
}
