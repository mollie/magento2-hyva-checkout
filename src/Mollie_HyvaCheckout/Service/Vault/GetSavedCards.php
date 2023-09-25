<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Service\Vault;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class GetSavedCards
{
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    private PaymentTokenRepositoryInterface $paymentTokenRepository;
    private SerializerInterface $serializer;

    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        SerializerInterface $serializer
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->serializer = $serializer;
    }

    /**
     * @return array{
     *      array{
     *          "public_hash": string,
     *          "type": string,
     *          "name": string,
     *          "maskedCC": string,
     *     }
     * }
     */
    public function execute(): array
    {
        $search = $this->searchCriteriaBuilderFactory->create();
        $search->addFilter('payment_method_code', 'mollie_methods_creditcard');

        $output = [];
        $items = $this->paymentTokenRepository->getList($search->create())->getItems();
        foreach ($items as $item) {
            $details = $this->serializer->unserialize($item->getTokenDetails());
            $details['public_hash'] = $item->getPublicHash();

            $output[] = $details;
        }

        return $output;
    }
}
