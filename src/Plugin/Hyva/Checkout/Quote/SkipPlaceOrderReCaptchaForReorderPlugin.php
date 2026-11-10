<?php

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin\Hyva\Checkout\Quote;

use Closure;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;
use Mollie\HyvaCheckout\Service\Order\ReorderInProgress;

class SkipPlaceOrderReCaptchaForReorderPlugin
{
    private ReorderInProgress $reorderInProgress;

    public function __construct(
        ReorderInProgress $reorderInProgress,
    ) {
        $this->reorderInProgress = $reorderInProgress;
    }

    /**
     * Hyvä Checkout 1.3.15 checks the Place Order reCAPTCHA on every storefront quote submit
     * (\Hyva\Checkout\Model\Quote\ValidationRules\ReCaptchaValidationRule). The Second Chance link recreates the
     * order server-side without a token, so the customer was sent to the homepage with "The reCAPTCHA challenge
     * for placing your order could not be verified" instead of reaching the payment page.
     */
    public function aroundValidate(QuoteValidationRuleInterface $subject, Closure $proceed, Quote $quote): array
    {
        if ($this->reorderInProgress->isActive()) {
            return [];
        }

        return $proceed($quote);
    }
}
