<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\HyvaCheckout\Observer\HyvaConfigGenerateBefore;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;

class RegisterModuleForHyvaConfig implements ObserverInterface
{
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly DirectoryList $directoryList
    ) {
    }

    public function execute(Observer $observer)
    {
        $config = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Mollie_HyvaCheckout');

        // Only use the path relative to the Magento base dir
        $extensions[] = ['src' => substr($path, strlen($this->directoryList->getRoot()) + 1)];

        $config->setData('extensions', $extensions);
    }
}
