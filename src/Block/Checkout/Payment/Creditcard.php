<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Block\Checkout\Payment;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\GetCustomerMandates;
use Mollie\Payment\Service\Mollie\SavedCardConsentText;

final class Creditcard extends Template
{
    private const CARD_LABEL_SLUG_MAP = [
        'Visa'             => 'visa',
        'Mastercard'       => 'mastercard',
        'American Express' => 'amex',
        'Maestro'          => 'maestro',
        'Carte Bancaire'   => 'cartebancaire',
        'V PAY'            => 'vpay',
    ];

    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        private readonly GetCustomerMandates $getCustomerMandates,
        private readonly SavedCardConsentText $savedCardConsentText,
        private readonly CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isComponentsEnabled(): bool
    {
        return $this->config->creditcardUseComponents() && $this->config->getProfileId();
    }

    public function isSavedCardsEnabled(): bool
    {
        return $this->config->creditcardEnableCustomersApi();
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getSavedMandates(): array
    {
        if (!$this->isSavedCardsEnabled() || !$this->isCustomerLoggedIn()) {
            return [];
        }

        return $this->getCustomerMandates->execute((int)$this->customerSession->getCustomerId());
    }

    public function getCardLogoUrl(string $cardLabel): string
    {
        $slug = self::CARD_LABEL_SLUG_MAP[$cardLabel] ?? null;

        if ($slug === null) {
            return '';
        }

        return $this->getViewFileUrl('Mollie_Payment::images/cards/' . $slug . '.svg');
    }

    public function getConsentText(): string
    {
        return $this->savedCardConsentText->execute();
    }
}
