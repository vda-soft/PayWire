<?php

declare(strict_types=1);

namespace PayWire\Tools\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\NameResolver\ResolvedName;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * PHPStan rule that forbids direct instantiation of DateTime/DateTimeImmutable
 * and usage of time-related functions (time, microtime, date, etc.)
 * outside of the Clock implementation.
 *
 * All code must use ClockRegistry::get()->now() instead.
 */
final class ForbidDirectDateTimeInstantiationRule implements Rule
{
    /** @var array<string, true> */
    private const array FORBIDDEN_CLASSES = [
        'DateTime' => true,
        'DateTimeImmutable' => true,
        'DateTimeZone' => true,
    ];

    /** @var array<string, true> */
    private const array FORBIDDEN_FUNCTIONS = [
        'time' => true,
        'microtime' => true,
        'date' => true,
        'gmdate' => true,
        'strtotime' => true,
        'mktime' => true,
        'gmmktime' => true,
        'getdate' => true,
        'gettimeofday' => true,
    ];

    /** @var array<string, true> */
    private const array ALLOWED_FILES = [
        'SystemClock.php' => true,
    ];

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param New_|FuncCall|Node $node
     *
     * @return array<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // Allow the Clock implementation itself to use DateTime
        if ($this->isAllowedFile($scope->getFile())) {
            return [];
        }

        return match (true) {
            $node instanceof New_ => $this->checkNewExpression($node, $scope),
            $node instanceof FuncCall => $this->checkFunctionCall($node, $scope),
            default => [],
        };
    }

    /**
     * @return array<RuleError>
     */
    private function checkNewExpression(New_ $new, Scope $scope): array
    {
        $className = $this->resolveShortName($new->class, $scope);

        return null !== $className && isset(self::FORBIDDEN_CLASSES[$className])
            ? [
                $this->createError(
                    'Direct instantiation of %s is forbidden. Use ClockRegistry::get()->now() instead.',
                    $className,
                    $new->getStartLine(),
                    'paywire.forbidDirectDateTimeInstantiation'
                ),
            ] : [];
    }

    /**
     * @return array<RuleError>
     */
    private function checkFunctionCall(FuncCall $funcCall, Scope $scope): array
    {
        $functionName = $this->resolveShortName($funcCall->name, $scope);

        return null !== $functionName && isset(self::FORBIDDEN_FUNCTIONS[$functionName])
            ? [$this->createError(
                'Direct usage of %s() is forbidden. Use ClockRegistry::get()->now() instead.',
                $functionName,
                $funcCall->getStartLine(),
                'paywire.forbidDirectTimeFunctionUsage'
            )]
            : [];
    }

    /**
     * Resolves a Name node to its short class/function name.
     */
    private function resolveShortName(Name|Node\Expr $name, Scope $scope): ?string
    {
        if (!$name instanceof Name) {
            return null;
        }

        $resolved = $scope->resolveName($name);

        return match (true) {
            $resolved instanceof ResolvedName => $resolved->getShortName(),
            \is_string($resolved) => $this->extractShortName($resolved),
            default => null,
        };
    }

    private function extractShortName(string $fullyQualifiedName): string
    {
        $parts = \explode('\\', $fullyQualifiedName);

        return \end($parts);
    }

    private function isAllowedFile(string $filePath): bool
    {
        $fileName = \basename($filePath);

        return isset(self::ALLOWED_FILES[$fileName]);
    }

    private function createError(string $message, string $name, int $line, string $identifier): RuleError
    {
        return RuleErrorBuilder::message(\sprintf($message, $name))
            ->identifier($identifier)
            ->line($line)
            ->build();
    }
}
