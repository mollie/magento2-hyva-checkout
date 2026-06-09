<?php

declare(strict_types=1);

namespace Mollie\HyvaCheckout\Magewire\Checkout\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Mollie\Payment\Service\Mollie\AvailableTerminals;

class WithTerminal extends Component
{
    private SessionCheckout $sessionCheckout;
    private CartRepositoryInterface $quoteRepository;
    private AvailableTerminals $availableTerminals;

    public string $selectedTerminal = '';

    public function __construct(
        SessionCheckout $sessionCheckout,
        CartRepositoryInterface $quoteRepository,
        AvailableTerminals $availableTerminals
    ) {
        $this->availableTerminals = $availableTerminals;
        $this->sessionCheckout = $sessionCheckout;
        $this->quoteRepository = $quoteRepository;
    }

    public function mount(): void
    {
        $quote = $this->sessionCheckout->getQuote();
        $terminalId = $quote->getPayment()->getAdditionalInformation('selected_terminal');

        if ($terminalId) {
            $this->selectedTerminal = $terminalId;
        }
    }

    /**
     * @return array{
     *      id: string,
     *      brand: string,
     *      model: string,
     *      serialNumber: string|null,
     *      description: string
     *  }
     */
    public function getTerminals(): array
    {
        return $this->availableTerminals->execute();
    }

    public function updatedSelectedTerminal(): string
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation('selected_terminal', $this->selectedTerminal);
        $this->quoteRepository->save($quote);

        return $this->selectedTerminal;
    }
}
