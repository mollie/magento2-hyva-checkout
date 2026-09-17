<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Test\Integration\Observer\SalesQuoteCollectTotalsBefore;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\ResourceModel\Quote\Payment as PaymentResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Mollie\HyvaCheckout\Observer\SalesQuoteCollectTotalsBefore\SetDefaultSelectedPaymentMethod;
use Mollie\HyvaCheckout\Test\Fakes\Observer\CountingObserverFake;
use Mollie\HyvaCheckout\Test\Fakes\Quote\Api\PaymentMethodManagementFake;
use Mollie\HyvaCheckout\Test\Fakes\Service\Quote\AvailablePaymentMethodsFake;
use PHPUnit\Framework\TestCase;

class SetDefaultSelectedPaymentMethodTest extends TestCase
{
    private const IDEAL = 'mollie_methods_ideal';
    private const CREDITCARD = 'mollie_methods_creditcard';
    private const APPLEPAY = 'mollie_methods_applepay';
    private const CHECK_MONEY_ORDER = 'checkmo';

    private ?ObjectManager $objectManager = null;
    private ?PaymentMethodManagementFake $paymentMethodManagement = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->paymentMethodManagement = new PaymentMethodManagementFake();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->objectManager->removeSharedInstance(SetDefaultSelectedPaymentMethod::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testSetsTheDefaultMethodOnAQuoteThatHasToRecollectItsTotals(): void
    {
        $quote = $this->loadQuote();
        $this->persistPaymentMethod($quote, null);
        $observer = $this->useObserverThatFailsWhenItCallsItself();

        $this->markQuoteToRecollectTotals($quote);

        $reloadedQuote = $this->reloadQuote($quote);
        $this->assertEquals(0, $reloadedQuote->getTriggerRecollect());
        $this->assertSame(self::IDEAL, $reloadedQuote->getPayment()->getMethod());
        $this->assertLessThanOrEqual(2, $observer->getDeepestDepth());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method first_mollie_method
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/allowspecific 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/specificcountry NL
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/active 1
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testReplacesAPersistedMethodThatIsNotAvailableForTheQuoteCountryWhenTheTotalsAreRecollected(): void
    {
        $quote = $this->loadQuote();
        $this->persistPaymentMethod($quote, self::IDEAL);
        $this->useObserverThatFailsWhenItCallsItself();

        $this->markQuoteToRecollectTotals($quote);

        $reloadedMethod = (string)$this->reloadQuote($quote)->getPayment()->getMethod();
        $this->assertStringStartsWith('mollie_methods_', $reloadedMethod);
        $this->assertNotSame(self::IDEAL, $reloadedMethod);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testSetsTheConfiguredDefaultMethod(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);

        $this->execute($quote, $this->availableMethods(self::CHECK_MONEY_ORDER, self::IDEAL));

        $this->assertSame([self::IDEAL], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(self::IDEAL, $quote->getPayment()->getMethod());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method first_mollie_method
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testSetsTheFirstAvailableMollieMethodAndSkipsOtherProviders(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);

        $this->execute($quote, $this->availableMethods(self::CHECK_MONEY_ORDER, self::IDEAL, self::CREDITCARD));

        $this->assertSame([self::IDEAL], $this->paymentMethodManagement->getMethodsThatWereSet());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method first_mollie_method
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testSkipsApplePayWhenLookingForTheFirstMollieMethod(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);

        $this->execute($quote, $this->availableMethods(self::APPLEPAY, self::CREDITCARD));

        $this->assertSame([self::CREDITCARD], $this->paymentMethodManagement->getMethodsThatWereSet());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method first_mollie_method
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testLeavesTheQuoteAloneWhenNoMollieMethodIsAvailable(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);

        $this->execute($quote, $this->availableMethods(self::CHECK_MONEY_ORDER));

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertNull($quote->getPayment()->getMethod());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testLeavesTheQuoteAloneWhenTheConfiguredDefaultMethodIsNotAvailable(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);

        $this->execute($quote, $this->availableMethods(self::CREDITCARD));

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertNull($quote->getPayment()->getMethod());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testKeepsTheCurrentMethodWhenItIsStillAvailable(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(self::CREDITCARD);

        $this->execute($quote, $this->availableMethods(self::IDEAL, self::CREDITCARD));

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(self::CREDITCARD, $quote->getPayment()->getMethod());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method first_mollie_method
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testReplacesTheCurrentMethodWhenItIsNoLongerAvailable(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(self::IDEAL);

        $this->execute($quote, $this->availableMethods(self::CHECK_MONEY_ORDER, self::CREDITCARD));

        $this->assertSame([self::CREDITCARD], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(self::CREDITCARD, $quote->getPayment()->getMethod());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testKeepsAnUnavailableMethodWhenNoDefaultMethodIsConfigured(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(self::IDEAL);
        $availableMethods = $this->availableMethods(self::CREDITCARD);

        $this->execute($quote, $availableMethods);

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(self::IDEAL, $quote->getPayment()->getMethod());
        $this->assertSame(0, $availableMethods->getNumberOfLookups());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testDoesNotTouchTheQuoteWhenItHasNoShippingCountry(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);
        $quote->getShippingAddress()->setCountryId(null);
        $availableMethods = $this->availableMethods(self::IDEAL);

        $this->execute($quote, $availableMethods);

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertNull($quote->getPayment()->getMethod());
        $this->assertSame(0, $availableMethods->getNumberOfLookups());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_virtual_product_and_address.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testSetsTheMethodOnAVirtualQuoteWithoutShippingCountry(): void
    {
        $quote = $this->loadQuoteByReservedOrderId('test_order_with_virtual_product');
        $quote->getPayment()->setMethod(null);
        $quote->getShippingAddress()->setCountryId(null);

        $this->execute($quote, $this->availableMethods(self::IDEAL));

        $this->assertSame([self::IDEAL], $this->paymentMethodManagement->getMethodsThatWereSet());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 0
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testDoesNothingWhenTheModuleIsDisabled(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);
        $availableMethods = $this->availableMethods(self::IDEAL);

        $this->execute($quote, $availableMethods);

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(0, $availableMethods->getNumberOfLookups());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout magento_luma
     */
    public function testDoesNothingWhenTheLumaCheckoutIsActive(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(null);
        $availableMethods = $this->availableMethods(self::IDEAL);

        $this->execute($quote, $availableMethods);

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(0, $availableMethods->getNumberOfLookups());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method mollie_methods_ideal
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testDoesNothingForAQuoteWithoutAnId(): void
    {
        $quote = $this->objectManager->create(Quote::class)->setStoreId(1);
        $availableMethods = $this->availableMethods(self::IDEAL);

        $this->execute($quote, $availableMethods);

        $this->assertSame([], $this->paymentMethodManagement->getMethodsThatWereSet());
        $this->assertSame(0, $availableMethods->getNumberOfLookups());
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/default_selected_method first_mollie_method
     * @magentoConfigFixture default_store hyva_themes_checkout/general/checkout default
     */
    public function testRestoresThePreviousMethodWhenPersistingFails(): void
    {
        $quote = $this->loadQuoteWithPaymentMethod(self::IDEAL);
        $this->paymentMethodManagement->givenSetFails();

        $this->execute($quote, $this->availableMethods(self::CREDITCARD));

        $this->assertSame(self::IDEAL, $quote->getPayment()->getMethod());
    }

    private function execute(Quote $quote, AvailablePaymentMethodsFake $availableMethods): void
    {
        $observer = $this->objectManager->create(SetDefaultSelectedPaymentMethod::class, [
            'paymentMethodManagement' => $this->paymentMethodManagement,
            'availablePaymentMethods' => $availableMethods,
        ]);

        $observer->execute($this->createEvent($quote));
    }

    private function availableMethods(string ...$codes): AvailablePaymentMethodsFake
    {
        return (new AvailablePaymentMethodsFake())->withCodes(...$codes);
    }

    private function createEvent(Quote $quote): Observer
    {
        $event = $this->objectManager->create(Observer::class);
        $event->setData('quote', $quote);

        return $event;
    }

    private function useObserverThatFailsWhenItCallsItself(): CountingObserverFake
    {
        $fake = $this->objectManager->create(CountingObserverFake::class, [
            'delegate' => $this->objectManager->create(SetDefaultSelectedPaymentMethod::class),
        ]);

        $this->objectManager->addSharedInstance($fake, SetDefaultSelectedPaymentMethod::class);

        return $fake;
    }

    private function loadQuote(): Quote
    {
        return $this->loadQuoteByReservedOrderId('test_order_1');
    }

    private function loadQuoteWithPaymentMethod(?string $methodCode): Quote
    {
        $quote = $this->loadQuote();
        $quote->getPayment()->setMethod($methodCode);

        return $quote;
    }

    private function loadQuoteByReservedOrderId(string $reservedOrderId): Quote
    {
        $quote = $this->objectManager->create(Quote::class);
        $this->objectManager->get(QuoteResource::class)->load($quote, $reservedOrderId, 'reserved_order_id');

        return $quote;
    }

    private function reloadQuote(Quote $quote): Quote
    {
        /** @var Quote $reloadedQuote */
        $reloadedQuote = $this->objectManager->get(CartRepositoryInterface::class)->get((int)$quote->getId());

        return $reloadedQuote;
    }

    private function persistPaymentMethod(Quote $quote, ?string $methodCode): void
    {
        $payment = $quote->getPayment();
        $payment->setMethod($methodCode);

        $this->objectManager->get(PaymentResource::class)->save($payment);
    }

    private function markQuoteToRecollectTotals(Quote $quote): void
    {
        $productIds = array_map(
            static fn ($item): int => (int)$item->getProductId(),
            $quote->getAllItems()
        );

        $this->objectManager->get(QuoteResource::class)->markQuotesRecollect($productIds);
    }
}
