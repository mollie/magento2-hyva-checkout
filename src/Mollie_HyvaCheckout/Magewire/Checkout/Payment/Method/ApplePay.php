<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magewirephp\Magewire\Component\Form;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\ApplePayIntegrationType;
use Rakit\Validation\Validator;

class ApplePay extends Form
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    private UrlInterface $url;

    private Session $checkoutSession;

    private StoreManagerInterface $storeManager;

    private CartRepositoryInterface $cartRepository;

    private Config $config;

    public string $amount = '';

    public string $countryId = '';

    public string $currencyCode = '';

    public string $storeName = '';

    public string $time = '';

    public function __construct(
        Validator $validator,
        UrlInterface $url,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository,
        Config $config
    ) {
        parent::__construct($validator);

        $this->url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->config = $config;
    }

    public function mount(): void
    {
        $cart = $this->checkoutSession->getQuote();
        $this->countryId = $cart->getBillingAddress()->getCountryId();
        $this->currencyCode = $cart->getQuoteCurrencyCode();
        $this->storeName = $this->storeManager->getStore()->getName();
    }

    public function directIntegrationIsEnabled(): bool
    {
        return $this->config->applePayIntegrationType() == ApplePayIntegrationType::DIRECT;
    }

    public function getApplePayValidationUrl(): string
    {
        return $this->url->getUrl('mollie/checkout/applePayValidation', ['_secure' => true]);
    }

    public function boot(): void
    {
        $this->amount = $this->checkoutSession->getQuote()->getGrandTotal();
    }

    public function setApplePayPaymentToken(string $token): string
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->getPayment()->setAdditionalInformation('applepay_payment_token', $token);

        $this->cartRepository->save($quote);

        return $token;
    }
}
