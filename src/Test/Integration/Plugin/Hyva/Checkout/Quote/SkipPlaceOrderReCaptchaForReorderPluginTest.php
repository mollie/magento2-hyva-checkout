<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Test\Integration\Plugin\Hyva\Checkout\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Mollie\HyvaCheckout\Plugin\Hyva\Checkout\Quote\SkipPlaceOrderReCaptchaForReorderPlugin;
use Mollie\HyvaCheckout\Plugin\Mollie\Service\Order\MarkReorderInProgressPlugin;
use Mollie\HyvaCheckout\Service\Order\ReorderInProgress;
use Mollie\Payment\Service\Order\Reorder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SkipPlaceOrderReCaptchaForReorderPluginTest extends TestCase
{
    private const FAILED = ['reCAPTCHA failed'];

    private ?ObjectManager $objectManager = null;
    private ?ReorderInProgress $reorderInProgress = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->reorderInProgress = $this->objectManager->create(ReorderInProgress::class);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testKeepsTheValidationOutsideAReorder(): void
    {
        $result = $this->validate();

        $this->assertSame(self::FAILED, $result);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testSkipsTheValidationWhileReordering(): void
    {
        $results = [];
        $this->reorder(function () use (&$results) {
            $results[] = $this->validate();

            return $this->createMock(OrderInterface::class);
        });

        $this->assertSame([[]], $results);
        $this->assertSame(self::FAILED, $this->validate());
    }

    /**
     * @magentoAppArea frontend
     */
    public function testValidatesAgainWhenTheReorderFails(): void
    {
        try {
            $this->reorder(function () {
                throw new RuntimeException('Reorder failed');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Reorder failed', $exception->getMessage());
        }

        $this->assertSame(self::FAILED, $this->validate());
    }

    private function reorder(callable $create): void
    {
        /** @var MarkReorderInProgressPlugin $plugin */
        $plugin = $this->objectManager->create(MarkReorderInProgressPlugin::class, [
            'reorderInProgress' => $this->reorderInProgress,
        ]);

        $plugin->aroundCreate(
            $this->createMock(Reorder::class),
            \Closure::fromCallable($create),
            $this->createMock(OrderInterface::class),
        );
    }

    private function validate(): array
    {
        /** @var SkipPlaceOrderReCaptchaForReorderPlugin $plugin */
        $plugin = $this->objectManager->create(SkipPlaceOrderReCaptchaForReorderPlugin::class, [
            'reorderInProgress' => $this->reorderInProgress,
        ]);

        return $plugin->aroundValidate(
            $this->createMock(QuoteValidationRuleInterface::class),
            fn () => self::FAILED,
            $this->objectManager->create(Quote::class),
        );
    }
}
