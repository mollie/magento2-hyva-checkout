<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Observer\SalesQuoteCollectTotalsBefore;

use Hyva\Checkout\Model\CheckoutInformation\Luma;
use Hyva\Checkout\Model\ConfigData\HyvaThemes\SystemConfigGeneral as HyvaCheckoutConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Config;

class SetDefaultSelectedPaymentMethod implements ObserverInterface
{
    private PaymentInterfaceFactory $paymentFactory;
    private Config $config;
    private HyvaCheckoutConfig $hyvaCheckoutConfig;
    private PaymentMethodManagementInterface $paymentMethodManagement;
    private PaymentMethodListInterface $paymentMethodList;

    private array $methodList = [];
    private int $storeId;

    public function __construct(
        HyvaCheckoutConfig $hyvaCheckoutConfig,
        Config $config,
        PaymentInterfaceFactory $paymentFactory,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentMethodListInterface $paymentMethodList
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->config = $config;
        $this->hyvaCheckoutConfig = $hyvaCheckoutConfig;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentMethodList = $paymentMethodList;
    }

    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getData('quote');

        if (!$this->config->isModuleEnabled((int)$quote->getStoreId())) {
            return;
        }

        // Don't override if the quote isn't available yet or if a payment method is already set.
        if (!$quote->getId() ||
            !$this->config->getApiKey((int)$quote->getStoreId()) ||
            $this->quoteHasActivePaymentMethod($quote)) {
            return;
        }

        $this->storeId = (int)$quote->getStoreId();
        $defaultMethod = $this->config->getDefaultSelectedMethod();
        if (!$defaultMethod) {
            return;
        }

        if ($defaultMethod == 'first_mollie_method') {
            $defaultMethod = $this->getFirstAvailableMollieMethod();
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
        try {
            $this->paymentMethodManagement->set($quote->getId(), $payment);
        } catch (InvalidTransitionException $exception) {
            // We are not able to set the payment method. Probably the address is not set yet.
        }
    }

    /**
     * Check if that method is enabled for the current store
     */
    private function isMethodActive(string $methodCode): bool
    {
        $methods = $this->getMethodList();

        /** @var PaymentMethodInterface $method */
        foreach ($methods as $method) {
            if ($method->getCode() === $methodCode) {
                return $method->getIsActive();
            }
        }

        return false;
    }

    private function isHyvaCheckoutActive(): bool
    {
        return $this->hyvaCheckoutConfig->getCheckout() !== Luma::NAMESPACE;
    }

    private function quoteHasActivePaymentMethod(Quote $quote): bool
    {
        return $quote->getPayment()->getMethod() !== null;
    }

    private function getFirstAvailableMollieMethod(): ?string
    {
        $methods = $this->getMethodList();

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

    private function getMethodList(): array
    {
        if ($this->methodList !== []) {
            return $this->methodList;
        }

        $this->methodList = $this->paymentMethodList->getList($this->storeId);
        return $this->methodList;
    }
}
