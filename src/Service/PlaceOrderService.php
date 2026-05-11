<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Service;

use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\Manager;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Service\Mollie\FormatExceptionMessages;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;

class PlaceOrderService extends AbstractPlaceOrderService
{
    private OrderRepositoryInterface $orderRepository;

    private Manager $messageManager;

    private UrlInterface $url;

    private Session $checkoutSession;
    private RedirectUrl $redirectUrl;
    private FormatExceptionMessages $formatExceptionMessages;

    public function __construct(
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        Manager $messageManager,
        UrlInterface $url,
        Session $checkoutSession,
        RedirectUrl $redirectUrl,
        FormatExceptionMessages $formatExceptionMessages
    ) {
        parent::__construct($cartManagement);
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->redirectUrl = $redirectUrl;
        $this->formatExceptionMessages = $formatExceptionMessages;
    }

    public function canHandle(string $code): bool
    {
        return substr($code, 0, 6) == 'mollie';
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory, ?int $orderId = null): EvaluationResultInterface
    {
        return $resultFactory->createSuccess();
    }

    public function canPlaceOrder(): bool
    {
        return true;
    }

    public function canRedirect(): bool
    {
        return true;
    }

    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        $order = $this->orderRepository->get($orderId);
        /** @var \Mollie\Payment\Model\Mollie $method */
        $method = $quote->getPayment()->getMethodInstance();

        try {
            return $this->redirectUrl->execute($method, $order);
        } catch (ApiException $exception) {
            $this->messageManager->addErrorMessage($this->formatExceptionMessages->execute($exception));
            $this->checkoutSession->restoreQuote();

            return $this->url->getUrl('checkout/cart');
        } catch (LocalizedException $exception) {
            $this->checkoutSession->restoreQuote();
            throw new LocalizedException(__($this->formatExceptionMessages->execute($exception)));
        }
    }
}
