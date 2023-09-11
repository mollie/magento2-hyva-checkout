<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Service;

use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\Manager;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;

class PlaceOrderService extends AbstractPlaceOrderService
{
    private OrderRepositoryInterface $orderRepository;

    private Data $paymentHelper;
    private Manager $messageManager;

    private UrlInterface $url;

    private Session $checkoutSession;
    private RedirectUrl $redirectUrl;

    public function __construct(
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        Data $paymentHelper,
        Manager $messageManager,
        UrlInterface $url,
        Session $checkoutSession,
        RedirectUrl $redirectUrl
    ) {
        parent::__construct($cartManagement);
        $this->orderRepository = $orderRepository;
        $this->paymentHelper = $paymentHelper;
        $this->messageManager = $messageManager;
        $this->url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->redirectUrl = $redirectUrl;
    }

    public function canPlaceOrder(): bool
    {
        return true;
    }

    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        $order = $this->orderRepository->get($orderId);
        /** @var \Mollie\Payment\Model\Mollie $method */
        $method = $quote->getPayment()->getMethodInstance();

        if ($method instanceof CreditcardVault) {
            $method = $this->paymentHelper->getMethodInstance('mollie_methods_creditcard');
        }

        try {
            return $this->redirectUrl->execute($method, $order);
        } catch (ApiException $exception) {
            $this->messageManager->addErrorMessage($this->formatExceptionMessage($exception));
            $this->checkoutSession->restoreQuote();

            return $this->url->getUrl('checkout/cart');
        }
    }

    public function formatExceptionMessage(\Exception $exception): string
    {
        if (stripos(
            $exception->getMessage(),
            'The webhook URL is invalid because it is unreachable from Mollie\'s point of view'
        ) !== false) {
            return __(
                'The webhook URL is invalid because it is unreachable from Mollie\'s point of view. ' .
                'View this article for more information: ' .
                'https://github.com/mollie/magento2/wiki/Webhook-Communication-between-your-Magento-webshop-and-Mollie'
            );
        }

        return __('Something went wrong while placing the order. Error: "%1"', $exception->getMessage());
    }
}
