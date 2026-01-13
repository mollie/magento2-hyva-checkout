<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Block\Checkout\Payment;

use Magento\Framework\View\Element\Template;
use Mollie\Payment\Config;

class Creditcard extends Template
{
    private Config $config;

    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function isComponentsEnabled(): bool
    {
        return $this->config->creditcardUseComponents() && $this->config->getProfileId();
    }
}
