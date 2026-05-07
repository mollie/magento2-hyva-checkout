<?php

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin\Mollie\Service\Order;

use Magento\Framework\UrlInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\RedirectUserWhenTransactionFails;
use Mollie\Payment\Service\Order\RedirectOnError;

class RedirectOnErrorPlugin
{
    private Config $config;
    private UrlInterface $urlBuilder;
    private \Hyva\Checkout\Model\Config $hyvaCheckoutConfig;

    public function __construct(
        Config $config,
        UrlInterface $urlBuilder,
        \Hyva\Checkout\Model\Config $hyvaCheckoutConfig,
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->hyvaCheckoutConfig = $hyvaCheckoutConfig;
    }

    /**
     * Replace hash-based checkout URLs with Hyva Checkout step URLs.
     *
     * The base Mollie module returns URLs like /checkout/#payment which work for
     * standard Magento checkout (hash-based routing), but Hyva Checkout uses
     * URL-based step navigation: /checkout/index/index/step/payment/
     */
    public function afterGetUrl(RedirectOnError $subject, string $result): string
    {
        if (!$this->hyvaCheckoutConfig->isHyvaCheckout($this->hyvaCheckoutConfig->getActiveCheckoutNamespace())) {
            return $result;
        }

        $redirectTo = $this->config->redirectWhenTransactionFailsTo();

        if ($redirectTo === RedirectUserWhenTransactionFails::REDIRECT_TO_CHECKOUT_SHIPPING) {
            return $this->urlBuilder->getUrl('checkout', ['step' => 'shipping']);
        }

        if ($redirectTo === RedirectUserWhenTransactionFails::REDIRECT_TO_CHECKOUT_PAYMENT) {
            return $this->urlBuilder->getUrl('checkout', ['step' => 'payment']);
        }

        return $result;
    }
}
