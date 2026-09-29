<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $core = ClassSet::fromDir(__DIR__ . '/src/PayWire/Core/src');

    $config->add(
        $core,
        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('PayWire\Core'))
            ->should(new DependsOnlyOnTheseNamespaces([
                    'PayWire\Core',
                    'Brick\Money',
                    'Symfony\Component\Uid',
                ]))
            ->because('<Core dependency error>'),
        Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('PayWire\Core\Domain'))
            ->should(new NotDependsOnTheseNamespaces([
                'PayWire\Core\Application',
                'PayWire\Core\Infrastructure',
            ]))
            ->because('<Core/Domain dependency error>'),
    );
};
