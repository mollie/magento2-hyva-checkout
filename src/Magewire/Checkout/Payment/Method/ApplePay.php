<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;
use Rakit\Validation\Validator;

class ApplePay extends Form
{
    protected $listeners = [
        'shipping_method_selected' => 'refresh',
        'coupon_code_applied' => 'refresh',
        'coupon_code_revoked' => 'refresh'
    ];

    private Session $checkoutSession;

    private CartRepositoryInterface $cartRepository;

    public string $amount = '';

    public string $countryId = '';

    public string $currencyCode = '';

    public string $time = '';

    public function __construct(
        Validator $validator,
        Session $checkoutSession,
        CartRepositoryInterface $cartRepository,
    ) {
        parent::__construct($validator);

        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
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
