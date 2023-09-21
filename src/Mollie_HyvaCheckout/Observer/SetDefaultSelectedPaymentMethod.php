<?php declare(strict_types=1);

namespace Mollie\HyvaCheckout\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Hyva\Checkout\Model\ConfigData\HyvaThemes\SystemConfigGeneral as HyvaCheckoutConfig;

class SetDefaultSelectedPaymentMethod implements ObserverInterface
{
    private PaymentInterfaceFactory $paymentFactory;
    private Config $config;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private HyvaCheckoutConfig $hyvaCheckoutConfig;

    public function __construct(
        HyvaCheckoutConfig $hyvaCheckoutConfig,
        ScopeConfigInterface $scopeConfig,
        Config $config,
        PaymentInterfaceFactory $paymentFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->hyvaCheckoutConfig = $hyvaCheckoutConfig;
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

        if (!$this->isMethodActive($defaultMethod)) {
            return;
        }

        // Skip setting default payment method if Luma checkout is enabled in Hyvä Checkout config
        if (!$this->isHyvaCheckoutActive()) {
            return;
        }

        /** @var \Magento\Quote\Api\Data\PaymentInterface $payment */
        $payment = $this->paymentFactory->create();
        $payment->setMethod($defaultMethod);

        $quote->setPayment($payment);
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
        return $this->hyvaCheckoutConfig->getCheckout() !== 'magento_luma';
    }

    private function quoteHasActivePaymentMethod(Quote $quote): bool
    {
        return $quote->getPayment()->getMethod() !== null;
    }
}
