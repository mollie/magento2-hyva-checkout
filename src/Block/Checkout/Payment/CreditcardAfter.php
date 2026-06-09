<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Block\Checkout\Payment;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Mollie\Payment\Config;

class CreditcardAfter extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        private readonly ResolverInterface $localeResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isComponentsEnabled(): bool
    {
        return $this->config->creditcardUseComponents() && $this->config->getProfileId();
    }

    public function getProfileId(): string
    {
        return (string)$this->config->getProfileId();
    }

    public function getLocale(): string
    {
        $locale = $this->config->getLocale();

        // Empty == autodetect, so use the store.
        if (!$locale || $locale == 'store') {
            return $this->localeResolver->getLocale();
        }

        return $locale;
    }

    public function isTestMode(): bool
    {
        return (bool)$this->config->isTestMode();
    }
}
