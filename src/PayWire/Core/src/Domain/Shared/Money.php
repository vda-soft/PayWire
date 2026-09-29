<?php

namespace PayWire\Core\Domain\Shared;

use Brick\Money\Money as BrickMoney;

final readonly class Money implements \Stringable
{
    private function __construct(private BrickMoney $money)
    {
    }

    public static function create(BrickMoney|string|int|float $amount, string $currency): self
    {
        if ($amount instanceof BrickMoney) {
            return new self($amount);
        }

        return new self(BrickMoney::of((string) $amount, $currency));
    }

    public static function zero(string $currency): self
    {
        return new self(BrickMoney::zero($currency));
    }

    public function add(self $money): self
    {
        return new self($this->money->plus($money->money));
    }

    public function subtract(self $money): self
    {
        return new self($this->money->minus($money->money));
    }

    public function multiplyBy(int|float $factor): self
    {
        return new self($this->money->multipliedBy((string) $factor));
    }

    public function divideBy(int|float $divisor): self
    {
        return new self($this->money->dividedBy((string) $divisor));
    }

    public function getValue(): string
    {
        return $this->money->getAmount()->toString();
    }

    /**
     * @param int<0, max> $scale
     */
    public function getValueAtScale(int $scale): string
    {
        return $this->money->getAmount()->toScale($scale)->toString();
    }

    public function __toString(): string
    {
        return (string) $this->money;
    }
}
