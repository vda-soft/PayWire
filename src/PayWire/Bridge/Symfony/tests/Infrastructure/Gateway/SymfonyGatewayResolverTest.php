<?php

declare(strict_types=1);

namespace PayWire\Bridge\Symfony\Tests\Infrastructure\Gateway;

use PayWire\Bridge\Symfony\Infrastructure\Gateway\SymfonyGatewayResolver;
use PayWire\Core\Application\GatewayNotRegistered;
use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Shared\Gateway;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class SymfonyGatewayResolverTest extends TestCase
{
    public function testResolvesRegisteredGateway(): void
    {
        $gateway = $this->createStub(Gateway::class);
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->expects(self::once())
            ->method('has')
            ->with(GatewayEnum::PayU->value)
            ->willReturn(true);
        $locator->expects(self::once())
            ->method('get')
            ->with(GatewayEnum::PayU->value)
            ->willReturn($gateway);

        $resolver = new SymfonyGatewayResolver($locator);

        self::assertSame($gateway, $resolver->resolve(GatewayEnum::PayU));
    }

    public function testThrowsWhenGatewayIsNotRegistered(): void
    {
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->expects(self::once())
            ->method('has')
            ->with(GatewayEnum::PayU->value)
            ->willReturn(false);
        $locator->expects(self::never())->method('get');

        $resolver = new SymfonyGatewayResolver($locator);

        $this->expectException(GatewayNotRegistered::class);

        $resolver->resolve(GatewayEnum::PayU);
    }

    public function testRejectsServiceThatDoesNotImplementGateway(): void
    {
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->expects(self::once())->method('has')->willReturn(true);
        $locator->expects(self::once())->method('get')->willReturn(new \stdClass());

        $resolver = new SymfonyGatewayResolver($locator);

        $this->expectException(\UnexpectedValueException::class);

        $resolver->resolve(GatewayEnum::PayU);
    }
}
