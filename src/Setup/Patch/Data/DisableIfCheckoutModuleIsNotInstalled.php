<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Setup\Patch\Data;

use Magento\Framework\Module\Manager;
use Magento\Framework\Module\Status;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class DisableIfCheckoutModuleIsNotInstalled implements DataPatchInterface
{
    private Manager $moduleManager;
    private Status $moduleStatus;

    public function __construct(
        Manager $moduleManager,
        Status $moduleStatus
    ) {
        $this->moduleStatus = $moduleStatus;
        $this->moduleManager = $moduleManager;
    }

    public function apply()
    {
        // Disable this module if Hyva Checkout is not installed. This is to prevent dependency errors.
        if ($this->moduleManager->isEnabled('Hyva_Checkout')) {
            return;
        }

        $this->moduleStatus->setIsEnabled(false, ['Mollie_HyvaCheckout']);
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
