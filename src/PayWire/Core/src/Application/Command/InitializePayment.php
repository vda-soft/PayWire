<?php

declare(strict_types=1);

namespace PayWire\Core\Application\Command;

use PayWire\Core\Domain\Payment\CustomerReference;
use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Payment\OrderReference;
use PayWire\Core\Domain\Payment\PaymentId;
use PayWire\Core\Domain\Shared\Money;

final readonly class InitializePayment implements \JsonSerializable
{
    public PaymentId $paymentId;

    public function __construct(
        public GatewayEnum $gateway,
        public Money $total,
        public string $description,
        public OrderReference $order,
        public CustomerReference $customer,
        public ?string $posId = null,
        ?PaymentId $paymentId = null,
    ) {
        $this->paymentId = $paymentId ?? new PaymentId();
    }

    /**
     * @return array{paymentId: string, gateway: value-of<GatewayEnum>, total: array{amount: string, currency: string}, description: string, order: array{type: string, id: string}, customer: array{email: string, id: string}, posId: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'paymentId' => $this->paymentId->jsonSerialize(),
            'gateway' => $this->gateway->value,
            'total' => $this->total->jsonSerialize(),
            'description' => $this->description,
            'order' => $this->order->jsonSerialize(),
            'customer' => $this->customer->jsonSerialize(),
            'posId' => $this->posId,
        ];
    }
}
