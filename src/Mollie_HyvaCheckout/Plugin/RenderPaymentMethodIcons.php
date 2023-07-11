<?php declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin;

use Hyva\Checkout\Model\MethodMetaData;

class RenderPaymentMethodIcons
{
    /**
     * Mollie payments do not have meta-data icons, so we need to make sure the icons are rendered without the
     * meta-data being set. The actual data will be populated by the PopulateIconData plugin.
     * @see \Mollie\HyvaCheckout\Plugin\PopulateIconData
     */
    public function afterCanRenderIcon(MethodMetaData $subject, $result)
    {
        if (strpos($subject->getMethod()->getCode(), 'mollie_methods_') === 0) {
            return true;
        }

        return $result;
    }
}
