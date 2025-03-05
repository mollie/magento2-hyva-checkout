<?php declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin;

use Hyva\Checkout\Model\MethodMetaDataInterface;
use Magento\Quote\Model\Cart\ShippingMethod;
use Mollie\Payment\Helper\General as MollieHelper;

class PopulateIconData
{
    private string $iconLibraryPathPrefix;
    private MollieHelper $mollieHelper;

    public function __construct(string $iconLibraryPathPrefix, MollieHelper $mollieHelper)
    {
        $this->iconLibraryPathPrefix = $iconLibraryPathPrefix;
        $this->mollieHelper = $mollieHelper;
    }

    public function beforeRenderIcon(MethodMetaDataInterface $subject): void
    {
        if (!$this->mollieHelper->useImage()) {
            return;
        }

        $method = $subject->getMethod();

        if ($method instanceof ShippingMethod) {
            return;
        }

        if (!$this->isMollieMethod($method->getCode())) {
            return;
        }

        $paymentIconPath = $this->getPaymentIconPath($method->getCode());

        $subject->setData(MethodMetaDataInterface::ICON, [
            'svg' => $paymentIconPath,
            'attributes' => [
                'title' => $method->getTitle()
            ]

        ]);
    }

    private function isMollieMethod(string $methodCode): bool
    {
        return strpos($methodCode, 'mollie_methods_') === 0;
    }

    private function getPaymentIconPath(string $methodCode): string
    {
        return $this->iconLibraryPathPrefix . '/' . str_replace('mollie_methods_', '', $methodCode);
    }
}
