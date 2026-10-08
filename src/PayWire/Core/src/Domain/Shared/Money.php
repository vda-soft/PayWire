<?php

declare(strict_types=1);

namespace PayWire\Core\Domain\Shared;

use Brick\Money\Money as BrickMoney;

final readonly class Money implements \Stringable, \JsonSerializable
{
    /**
     * @internal
     */
    public function __construct(
        public string $amount,
        public string $currency,
    ) {
    }

    public function __toString(): string
    {
        return $this->currency . ' ' . $this->amount;
    }

    public static function of(string|int|float $amount, string $currency): self
    {
        return new self((string) $amount, $currency);
    }

    public static function zero(string $currency): self
    {
        return new self('0', $currency);
    }

    public function add(self $money): self
    {
        return new self(
            $this->brick()->plus($money->brick())->getAmount()->toString(),
            $this->currency
        );
    }

    public function subtract(self $money): self
    {
        return new self(
            $this->brick()->minus($money->brick())->getAmount()->toString(),
            $this->currency
        );
    }

    public function multiplyBy(int|float $factor): self
    {
        return new self(
            $this->brick()->multipliedBy((string) $factor)->getAmount()->toString(),
            $this->currency
        );
    }

    public function divideBy(int|float $divisor): self
    {
        return new self(
            $this->brick()->dividedBy((string) $divisor)->getAmount()->toString(),
            $this->currency
        );
    }

    /**
     * @param int<0, max> $scale
     */
    public function getValueAtScale(int $scale): string
    {
        return $this->brick()->getAmount()->toScale($scale)->toString();
    }

    /** @return array{amount: string, currency: string} */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    private function brick(): BrickMoney
    {
        return BrickMoney::of($this->amount, $this->currency);
    }
}
