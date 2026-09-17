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
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Mollie\HyvaCheckout\Service\Quote\AvailablePaymentMethodsInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Logger\MollieLogger;

class SetDefaultSelectedPaymentMethod implements ObserverInterface
{
    public const FIRST_MOLLIE_METHOD = 'first_mollie_method';

    private const MOLLIE_METHOD_PREFIX = 'mollie_';
    private const METHODS_EXCLUDED_FROM_PRESELECTION = ['mollie_methods_applepay'];

    private bool $isSettingPaymentMethod = false;

    public function __construct(
        private readonly HyvaCheckoutConfig $hyvaCheckoutConfig,
        private readonly Config $config,
        private readonly PaymentMethodManagementInterface $paymentMethodManagement,
        private readonly AvailablePaymentMethodsInterface $availablePaymentMethods,
        private readonly MollieLogger $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        if ($this->isSettingPaymentMethod) {
            return;
        }

        /** @var Quote $quote */
        $quote = $observer->getData('quote');
        $quoteId = $this->getQuoteId($quote);
        if ($quoteId === null || !$this->canPreselectFor($quote)) {
            return;
        }

        $availableMethods = $this->availablePaymentMethods->getCodes($quote);
        if ($this->hasAvailablePaymentMethod($quote, $availableMethods)) {
            return;
        }

        $method = $this->getDefaultMethod(storeId($quote->getStoreId()), $availableMethods);
        if ($method === null) {
            return;
        }

        $this->setPaymentMethod($quote, $quoteId, $method);
    }

    private function getQuoteId(Quote $quote): ?int
    {
        $quoteId = $quote->getId();

        return is_numeric($quoteId) ? (int)$quoteId : null;
    }

    private function canPreselectFor(Quote $quote): bool
    {
        $storeId = storeId($quote->getStoreId());

        return $this->config->isModuleEnabled($storeId)
            && $this->config->getApiKey($storeId) !== ''
            && $this->config->getDefaultSelectedMethod($storeId) !== ''
            && $this->isHyvaCheckoutActive()
            && $this->quoteCanAcceptPaymentMethod($quote);
    }

    private function isHyvaCheckoutActive(): bool
    {
        return $this->hyvaCheckoutConfig->getCheckout() !== Luma::NAMESPACE;
    }

    private function quoteCanAcceptPaymentMethod(Quote $quote): bool
    {
        if ($quote->isVirtual()) {
            return true;
        }

        return (string)$quote->getShippingAddress()->getCountryId() !== '';
    }

    /**
     * @param list<string> $availableMethods
     */
    private function hasAvailablePaymentMethod(Quote $quote, array $availableMethods): bool
    {
        return in_array($quote->getPayment()->getMethod(), $availableMethods, true);
    }

    /**
     * @param list<string> $availableMethods
     */
    private function getDefaultMethod(?int $storeId, array $availableMethods): ?string
    {
        $configuredMethod = $this->config->getDefaultSelectedMethod($storeId);
        if ($configuredMethod === self::FIRST_MOLLIE_METHOD) {
            return $this->getFirstMollieMethod($availableMethods);
        }

        return in_array($configuredMethod, $availableMethods, true) ? $configuredMethod : null;
    }

    /**
     * @param list<string> $availableMethods
     */
    private function getFirstMollieMethod(array $availableMethods): ?string
    {
        $mollieMethods = array_values(array_filter($availableMethods, $this->canBePreselected(...)));

        return $mollieMethods[0] ?? null;
    }

    private function canBePreselected(string $methodCode): bool
    {
        return str_starts_with($methodCode, self::MOLLIE_METHOD_PREFIX)
            && !in_array($methodCode, self::METHODS_EXCLUDED_FROM_PRESELECTION, true);
    }

    private function setPaymentMethod(Quote $quote, int $quoteId, string $methodCode): void
    {
        $payment = $quote->getPayment();
        $previousMethodCode = $payment->getMethod();
        $payment->setMethod($methodCode);

        $this->isSettingPaymentMethod = true;
        try {
            $this->paymentMethodManagement->set($quoteId, $payment);
        } catch (LocalizedException $exception) {
            $payment->setMethod($previousMethodCode);
            $this->logger->addErrorLog(
                'Unable to preselect payment method ' . $methodCode,
                $exception->getMessage()
            );
        } finally {
            $this->isSettingPaymentMethod = false;
        }
    }
}
