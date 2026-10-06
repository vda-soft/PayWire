<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use Webmozart\Assert\Assert;

final readonly class OrderReference implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $id,
    ) {
        Assert::notEmpty($type, 'Order reference type cannot be empty.');
        Assert::notEmpty($id, 'Order reference id cannot be empty.');
    }

    /**
     * @return array{type: string, id: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
        ];
    }
}
