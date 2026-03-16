<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Magewire\Express;

use Exception;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Mollie\Payment\Api\Webapi\GetCustomerOrderInterface;

class WaitPage extends Component
{
    public string $token = '';
    public string $status = '';
    public string $currentStatus = '';
    public string $redirectToCartUrl = '';

    public function __construct(
        private readonly GetCustomerOrderInterface $getCustomerOrder,
        private readonly UrlInterface $urlBuilder,
    ) {}

    public function setToken(string $token): void
    {
        $this->token = $token;
        $this->redirectToCartUrl = $this->urlBuilder->getUrl(
            'mollie/express/redirectToCart',
            ['token' => $this->token]
        );
        $this->currentStatus = __('Current Status: Loading...')->render();
    }

    public function pollStatus(): void
    {
        if ($this->status === 'processing') {
            // Redirect already in progress — prevent duplicate calls
            return;
        }

        try {
            $order = $this->getCustomerOrder->byPaymentToken($this->token);
            $status = $order[0]['status'];
            $this->status = $status;
            $this->currentStatus = __('Current Status: %1', ucfirst($status))->render();

            if ($status === 'processing') {
                $this->redirect(
                    $this->urlBuilder->getUrl('mollie/express/redirect', ['token' => $this->token])
                );
            }
        } catch (NotFoundException $e) {
            // Order not yet created — keep polling silently
        } catch (Exception $e) {
            $this->status = 'error';
            $this->currentStatus = __('Something went wrong while fetching order. Retrying...')->render();
        }
    }
}
