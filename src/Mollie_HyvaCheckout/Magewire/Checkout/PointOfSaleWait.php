<?php

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Magewire\Checkout;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magewirephp\Magewire\Component;
use Mollie\Payment\Api\Webapi\GetCustomerOrderInterface;
use Mollie\Payment\Api\Webapi\ResetCartInterface;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForOrder;

class PointOfSaleWait extends Component
{
    private RequestInterface $requestInterface;
    private GetCustomerOrderInterface $getCustomerOrder;
    private ResetCartInterface $resetCart;
    private Transaction $transaction;
    private PaymentTokenForOrder $paymentTokenForOrder;
    private EncryptorInterface $encryptor;
    private OrderRepositoryInterface $orderRepository;

    public string $token = '';
    public string $status = '';
    public string $currentStatus = '';
    public string $incrementId = '';

    public function __construct(
        RequestInterface $requestInterface,
        GetCustomerOrderInterface $getCustomerOrder,
        ResetCartInterface $resetCart,
        Transaction $transaction,
        PaymentTokenForOrder $paymentTokenForOrder,
        EncryptorInterface $encryptor,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->requestInterface = $requestInterface;
        $this->getCustomerOrder = $getCustomerOrder;
        $this->resetCart = $resetCart;
        $this->transaction = $transaction;
        $this->paymentTokenForOrder = $paymentTokenForOrder;
        $this->encryptor = $encryptor;
        $this->orderRepository = $orderRepository;
    }

    public function mount(): void
    {
        $this->token = $this->requestInterface->getParam('token');
        $this->currentStatus = __('Current Status: Loading...')->render();

        $this->fetchOrderStatus();
    }

    public function fetchOrderStatus(): void
    {
        if ($this->status == 'processing') {
            // Redirecting can take a moment, prevent multiple calls
            return;
        }

        // This retrieves the status directly from Mollie
        $order = $this->getCustomerOrder->byHash($this->token);
        $status = $order[0]['status'];
        $this->status = $status;
        $this->incrementId = $order[0]['increment_id'];
        $this->currentStatus = __('Current Status: %1', ucfirst($status))->render();

        if ($status == 'processing') {
            $order = $this->getOrder();
            $paymentToken = $this->paymentTokenForOrder->execute($order);
            $url = $this->transaction->getRedirectUrl($order, $paymentToken);

            $this->redirect($url);
        }
    }

    public function retryOrder(): void
    {
        $this->resetCart->byHash($this->token);

        $order = $this->getOrder();
        $paymentToken = $this->paymentTokenForOrder->execute($order);

        $this->redirect('mollie/checkout/redirect', ['paymentToken' => $paymentToken]);
    }

    private function getOrder(): OrderInterface
    {
        $orderId = $this->encryptor->decrypt(base64_decode($this->token));

        return $this->orderRepository->get($orderId);
    }
}
