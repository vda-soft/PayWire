<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Payment;

use Webmozart\Assert\Assert;

final readonly class CustomerReference implements \JsonSerializable
{
    public function __construct(
        public string $email,
        public string $id,
    ) {
        Assert::email($email, 'Invalid email address');
    }

    /**
     * @return array{email: string, id: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'email' => $this->email,
            'id' => $this->id,
        ];
    }
}
