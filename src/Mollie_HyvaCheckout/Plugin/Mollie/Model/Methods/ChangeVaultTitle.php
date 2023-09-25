<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Plugin\Mollie\Model\Methods;

use Magento\Framework\Phrase;

class ChangeVaultTitle
{
    public function afterGetTitle(): Phrase
    {
        return __('Saved Credit Cards');
    }
}
