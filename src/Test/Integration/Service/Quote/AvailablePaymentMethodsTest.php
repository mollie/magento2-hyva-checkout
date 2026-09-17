<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Test\Integration\Service\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Mollie\HyvaCheckout\Service\Quote\AvailablePaymentMethodsInterface;
use PHPUnit\Framework\TestCase;

class AvailablePaymentMethodsTest extends TestCase
{
    private const IDEAL = 'mollie_methods_ideal';
    private const CREDITCARD = 'mollie_methods_creditcard';
    private const CHECK_MONEY_ORDER = 'checkmo';

    private ?ObjectManager $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/allowspecific 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/specificcountry US
     */
    public function testIncludesMethodsThatAllowTheQuoteCountry(): void
    {
        $quote = $this->loadQuote();

        $codes = $this->getCodes($quote);

        $this->assertContains(self::IDEAL, $codes);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/allowspecific 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/specificcountry NL
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/active 1
     */
    public function testExcludesMethodsRestrictedToOtherCountries(): void
    {
        $quote = $this->loadQuote();

        $codes = $this->getCodes($quote);

        $this->assertNotContains(self::IDEAL, $codes);
        $this->assertContains(self::CREDITCARD, $codes);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store general/country/default NL
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/allowspecific 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/specificcountry NL
     */
    public function testFallsBackToTheStoreDefaultCountryWhenTheQuoteHasNoCountry(): void
    {
        $quote = $this->loadQuote();
        $quote->getBillingAddress()->setCountryId(null);
        $quote->getShippingAddress()->setCountryId(null);

        $codes = $this->getCodes($quote);

        $this->assertContains(self::IDEAL, $codes);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 0
     */
    public function testExcludesInactiveMethods(): void
    {
        $quote = $this->loadQuote();

        $codes = $this->getCodes($quote);

        $this->assertNotContains(self::IDEAL, $codes);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/max_order_total 1
     */
    public function testExcludesMethodsAboveTheirMaximumOrderTotal(): void
    {
        $quote = $this->loadQuote();
        $quote->setBaseGrandTotal(100);

        $codes = $this->getCodes($quote);

        $this->assertNotContains(self::IDEAL, $codes);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/sort_order 20
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/sort_order 10
     */
    public function testOrdersTheMethodsBySortOrder(): void
    {
        $quote = $this->loadQuote();

        $codes = $this->getCodes($quote);

        $this->assertLessThan(array_search(self::IDEAL, $codes, true), array_search(self::CREDITCARD, $codes, true));
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_saved.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/checkmo/active 1
     */
    public function testIncludesMethodsOfOtherProviders(): void
    {
        $quote = $this->loadQuote();

        $codes = $this->getCodes($quote);

        $this->assertContains(self::CHECK_MONEY_ORDER, $codes);
    }

    /**
     * @return list<string>
     */
    private function getCodes(Quote $quote): array
    {
        return $this->objectManager->get(AvailablePaymentMethodsInterface::class)->getCodes($quote);
    }

    private function loadQuote(): Quote
    {
        $quote = $this->objectManager->create(Quote::class);
        $this->objectManager->get(QuoteResource::class)->load($quote, 'test_order_1', 'reserved_order_id');

        return $quote;
    }
}
