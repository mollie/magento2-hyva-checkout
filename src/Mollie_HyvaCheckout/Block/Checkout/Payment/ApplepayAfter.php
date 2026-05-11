<?php

namespace Mollie\HyvaCheckout\Block\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\ApplePayIntegrationType;
use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;

class ApplepayAfter extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        private readonly Session $checkoutSession,
        private readonly DirectoryHelper $directoryHelper,
        private readonly SupportedNetworks $supportedNetworks,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function directIntegrationIsEnabled(): bool
    {
        return $this->config->applePayIntegrationType() == ApplePayIntegrationType::DIRECT;
    }

    public function getCountryId(): string
    {
        $cart = $this->checkoutSession->getQuote();

        return (string)($cart->getBillingAddress()->getCountryId() ?: $this->directoryHelper->getDefaultCountry());
    }

    public function getCurrencyCode(): string
    {
        $cart = $this->checkoutSession->getQuote();

        return (string)$cart->getQuoteCurrencyCode();
    }

    public function getSupportedNetworks(): array
    {
        return $this->supportedNetworks->execute();
    }

    public function getStoreName(): string
    {
        return (string)$this->storeManager->getStore()->getName();
    }

    public function getApplePayValidationUrl(): string
    {
        return $this->url->getUrl('mollie/checkout/applePayValidation', ['_secure' => true]);
    }
}
