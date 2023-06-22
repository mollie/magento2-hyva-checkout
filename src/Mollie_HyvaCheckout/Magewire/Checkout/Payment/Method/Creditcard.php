<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component\Form;
use Mollie\Payment\Config;
use Rakit\Validation\Validator;

/**
 * @method static getMagewire()
 */
class Creditcard extends Form
{
    protected $rules = [
        'cardToken' => 'required',
    ];

    private CartRepositoryInterface $quoteRepository;

    private SessionCheckout $sessionCheckout;

    private ResolverInterface $localeResolver;

    private Config $config;

    public string $profileId = '';

    public bool $isTestMode = false;

    public string $locale = '';

    public string $cardToken = '';

    public function __construct(
        Validator $validator,
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        ResolverInterface $localeResolver,
        Config $config
    ) {
        parent::__construct($validator);

        $this->sessionCheckout = $sessionCheckout;
        $this->quoteRepository = $quoteRepository;
        $this->localeResolver = $localeResolver;
        $this->config = $config;
    }

    public function mount(): void
    {
        $this->profileId = (string)$this->config->getProfileId();
        $this->locale = $this->getLocale();
        $this->isTestMode = $this->config->isTestMode();
    }

    public function setCardToken(string $value): string
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation('card_token', $value);

        $this->quoteRepository->save($quote);

        return $value;
    }

    public function isComponentsEnabled(): bool
    {
        return $this->config->creditcardUseComponents() && $this->config->getProfileId();
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        $locale = $this->config->getLocale();

        // Empty == autodetect, so use the store.
        if (!$locale || $locale == 'store') {
            return $this->localeResolver->getLocale();
        }

        return $locale;
    }
}
