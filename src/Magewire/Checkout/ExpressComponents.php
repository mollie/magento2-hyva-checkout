<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Magewire\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Mollie\Payment\Service\Mollie\CreateSession;

class ExpressComponents extends Component
{
    public string $clientAccessToken = '';
    public bool $isReady = false;

    protected $listeners = [
        'shipping_address_saved' => 'checkReadiness',
        'guest_shipping_address_saved' => 'checkReadiness',
        'billing_address_saved' => 'checkReadiness',
        'guest_billing_address_saved' => 'checkReadiness',
        'shipping_address_activated' => 'checkReadiness',
        'billing_address_activated' => 'checkReadiness',
        'mollie_guest_email_saved' => 'checkReadiness',
    ];

    public function __construct(
        private readonly Session $checkoutSession,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CreateSession $createSession,
    ) {}

    public function mount(): void
    {
        $this->checkReadiness();
    }

    public function createSession(): void
    {
        $cart = $this->checkoutSession->getQuote();
        $this->clientAccessToken = $this->createSession->execute($cart);

        $this->cartRepository->save($cart);
    }

    public function createCheckoutSession(): void
    {
        $cart = $this->checkoutSession->getQuote();
        $this->clientAccessToken = $this->createSession->execute($cart, false);

        $this->cartRepository->save($cart);
    }

    public function checkReadiness(): void
    {
        $cart = $this->checkoutSession->getQuote();

        $email = $cart->getCustomerEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->isReady = false;
            return;
        }

        $address = $cart->getShippingAddress();
        $required = ['firstname', 'lastname', 'street', 'city', 'postcode', 'country_id'];

        foreach ($required as $field) {
            $value = $address->getData($field);
            if (empty($value) || (is_array($value) && empty(array_filter($value)))) {
                $this->isReady = false;
                return;
            }
        }

        $this->isReady = true;
    }
}
