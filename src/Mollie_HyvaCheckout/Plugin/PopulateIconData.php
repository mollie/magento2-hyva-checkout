<?php declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin;

use Hyva\Checkout\Model\MethodMetaDataInterface;
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

        $methodCode = $subject->getMethod()->getCode();
        if (!$this->isMollieMethod($methodCode)) {
            return;
        }

        $paymentIconPath = $this->getPaymentIconPath($methodCode);

        $subject->setData(MethodMetaDataInterface::ICON, [
            'svg' => $paymentIconPath
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
