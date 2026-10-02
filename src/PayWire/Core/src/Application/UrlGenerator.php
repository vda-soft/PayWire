<?php

declare(strict_types=1);

namespace PayWire\Core\Application;

interface UrlGenerator
{
    /** @param array<string, string|int|float> $routeParameters */
    public function generate(string $routeName, array $routeParameters): string;
}
