<?php

declare(strict_types=1);

namespace PayWire\Bridge\Symfony\Infrastructure\Gateway;

use PayWire\Core\Application\GatewayNotRegistered;
use PayWire\Core\Application\GatewayResolver;
use PayWire\Core\Domain\Payment\GatewayEnum;
use PayWire\Core\Domain\Shared\Gateway;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

final readonly class SymfonyGatewayResolver implements GatewayResolver
{
    /** @param ServiceProviderInterface<Gateway> $gateways */
    public function __construct(
        #[AutowireLocator('paywire.gateway', indexAttribute: 'gateway')]
        private ServiceProviderInterface $gateways,
    ) {
    }

    public function resolve(GatewayEnum $gateway): Gateway
    {
        if (!$this->gateways->has($gateway->value)) {
            throw GatewayNotRegistered::for($gateway);
        }

        $service = $this->gateways->get($gateway->value);

        if (!$service instanceof Gateway) {
            throw new \UnexpectedValueException(\sprintf(
                'Service registered for gateway "%s" must implement %s, got %s.',
                $gateway->value,
                Gateway::class,
                \get_debug_type($service),
            ));
        }

        return $service;
    }
}
