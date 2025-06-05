<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Observer\SalesQuoteCollectTotalsBefore;

use Hyva\Checkout\Model\CheckoutInformation\Luma;
use Hyva\Checkout\Model\ConfigData\HyvaThemes\SystemConfigGeneral as HyvaCheckoutConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;

class SetDefaultSelectedPaymentMethod implements ObserverInterface
{
    private PaymentInterfaceFactory $paymentFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private HyvaCheckoutConfig $hyvaCheckoutConfig;
    private PaymentMethodManagementInterface $paymentMethodManagement;

    public function __construct(
        HyvaCheckoutConfig $hyvaCheckoutConfig,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        PaymentInterfaceFactory $paymentFactory,
        StoreManagerInterface $storeManager,
        PaymentMethodManagementInterface $paymentMethodManagement,
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->hyvaCheckoutConfig = $hyvaCheckoutConfig;
        $this->paymentMethodManagement = $paymentMethodManagement;
    }

    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getData('quote');

        // Don't override if a payment method is already set.
        if ($this->quoteHasActivePaymentMethod($quote)) {
            return;
        }

        $defaultMethod = $this->config->getDefaultSelectedMethod($this->storeManager->getStore()->getId());
        if (!$defaultMethod) {
            return;
        }

        if ($defaultMethod == 'first_mollie_method') {
            $defaultMethod = $this->getFirstAvailableMollieMethod($quote);
        }

        if ($defaultMethod && !$this->isMethodActive($defaultMethod)) {
            return;
        }

        // Skip setting default payment method if Luma checkout is enabled in Hyvä Checkout config
        if (!$this->isHyvaCheckoutActive()) {
            return;
        }

        /** @var \Magento\Quote\Api\Data\PaymentInterface $payment */
        $payment = $quote->getPayment() ?: $this->paymentFactory->create();
        $payment->setMethod($defaultMethod);

        $quote->setPayment($payment);
        $this->paymentMethodManagement->set($quote->getId(), $payment);
    }

    /**
     * Check if that method is enabled for the current store
     */
    private function isMethodActive(string $methodCode): bool
    {
        return $this->scopeConfig->isSetFlag(
            sprintf('payment/%s/active', $methodCode),
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode()
        );
    }

    private function isHyvaCheckoutActive(): bool
    {
        return $this->hyvaCheckoutConfig->getCheckout() !== Luma::NAMESPACE;
    }

    private function quoteHasActivePaymentMethod(Quote $quote): bool
    {
        return $quote->getPayment()->getMethod() !== null;
    }

    private function getFirstAvailableMollieMethod(Quote $quote): ?string
    {
        $methods = $this->paymentMethodManagement->getList($quote->getId());

        foreach ($methods as $method) {
            $methodCode = $method->getCode();
            if (strpos($methodCode, 'mollie_') === 0 &&
                $methodCode != 'mollie_methods_applepay' &&
                $this->isMethodActive($methodCode)
            ) {
                return $methodCode;
            }
        }

        return null;
    }
}
