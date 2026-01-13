<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magewirephp\Magewire\Component;
use Mollie\HyvaCheckout\Service\Vault\GetSavedCards;

class CreditcardVault extends Component
{
    private SessionCheckout $sessionCheckout;
    private CartRepositoryInterface $quoteRepository;

    public ?string $public_hash = '';
    private GetSavedCards $getSavedCards;

    public function __construct(
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        GetSavedCards $getSavedCards
    ) {
        $this->sessionCheckout = $sessionCheckout;
        $this->quoteRepository = $quoteRepository;
        $this->getSavedCards = $getSavedCards;
    }

    public function mount(): void
    {
        $quote = $this->sessionCheckout->getQuote();
        $this->public_hash = $quote->getPayment()->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);
    }

    public function getSavedCards(): array
    {
        return $this->getSavedCards->execute();
    }

    /**
     * @param mixed $value
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updated($value)
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $this->public_hash);
        $quote->getPayment()->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $quote->getCustomerId());

        $this->quoteRepository->save($quote);

        return $value;
    }
}
