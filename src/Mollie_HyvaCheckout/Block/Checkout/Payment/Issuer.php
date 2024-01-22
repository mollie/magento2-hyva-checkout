<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Block\Checkout\Payment;

use Magento\Framework\View\Element\Template;
use Mollie\Payment\Helper\General;

class Issuer extends Template
{
    private General $helper;

    public function __construct(
        Template\Context $context,
        General $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helper = $helper;
    }

    public function getTemplate(): string
    {
        $listType = $this->helper->getIssuerListType($this->getData('mollie_method'));

        if ($listType == 'none') {
            return 'Mollie_HyvaCheckout::component/payment/method/issuer/none.phtml';
        }

        if ($listType == 'dropdown') {
            return 'Mollie_HyvaCheckout::component/payment/method/issuer/dropdown.phtml';
        }

        return 'Mollie_HyvaCheckout::component/payment/method/issuer/list.phtml';
    }
}
