<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;
use Mollie\Payment\Service\Mollie\GetIssuers;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Rakit\Validation\Validator;

/**
 * @method static getMagewire()
 */
class WithIssuer extends Form
{
    protected $rules = [
        'mollie_issuer' => 'required',
    ];

    public array $issuers = [];

    public string $selectedIssuer = '';

    private SessionCheckout $sessionCheckout;

    private CartRepositoryInterface $quoteRepository;

    private MollieApiClient $mollieApiClient;

    private GetIssuers $getIssuers;

    private string $method;

    public function __construct(
        Validator $validator,
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        MollieApiClient $mollieApiClient,
        GetIssuers $getIssuers,
        string $method
    ) {
        parent::__construct($validator);
        $this->sessionCheckout = $sessionCheckout;
        $this->mollieApiClient = $mollieApiClient;
        $this->getIssuers = $getIssuers;
        $this->quoteRepository = $quoteRepository;
        $this->method = $method;
    }

    public function mount(): void
    {
        $quote = $this->sessionCheckout->getQuote();

        $mollieApiClient = $this->mollieApiClient->loadByStore();
        $this->issuers = $this->getIssuers->execute($mollieApiClient, $this->method, 'list');

        if ($selectedIssuer = $quote->getPayment()->getAdditionalInformation('selected_issuer')) {
            $this->selectedIssuer = $selectedIssuer;
        }
    }

    public function updatedSelectedIssuer(string $value): ?string
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation('selected_issuer', $value);

        $this->quoteRepository->save($quote);

        return $value;
    }
}
