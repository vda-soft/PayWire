#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CodeCharta Converter for PHP projects
 * Converts PHP metrics (from phpmetrics + PHP-Parser) to CodeCharta .cc.json format
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Symfony\Component\Finder\Finder;

$inputFile = $argv[1] ?? 'var/codecharta-metrics.json';
$outputFile = $argv[2] ?? 'var/codecharta.cc.json';

if (!file_exists($inputFile)) {
    // Try the phpmetrics HTML report location
    $altInput = 'var/metrics/js/latest.json';
    if (file_exists($altInput)) {
        $inputFile = $altInput;
    } else {
        fwrite(STDERR, "Input file not found: $inputFile\n");
        exit(1);
    }
}

echo "Reading metrics from: $inputFile\n";
$metricsData = json_decode(file_get_contents($inputFile), true);

if (!$metricsData) {
    fwrite(STDERR, "Failed to parse JSON from $inputFile\n");
    exit(1);
}

echo "Metrics loaded successfully\n";

// TODO: Parse PHP files with PHP-Parser
// TODO: Build CodeCharta nodes/edges structure
// TODO: Write output

echo "Output would go to: $outputFile\n";

/**
 * Step 1: Parse all PHP files in src/ and collect AST data
 */
function parsePhpFiles(string $srcDir): array
{
    // Use PHP 8.4 parser for modern syntax (readonly, property hooks, etc.)
    $parser = (new \PhpParser\ParserFactory)->createForNewestSupportedVersion(new Lexer(['usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos']]));
    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver());

    $filesData = [];
    $finder = new Finder();
    // Only our source code, not vendor
    $finder->files()
        ->in($srcDir . '/PayWire')
        ->name('*.php')
        ->exclude('vendor')
        ->exclude('tests');

    foreach ($finder as $file) {
        $relativePath = $file->getRelativePathname();
        $code = file_get_contents($file->getRealPath());
        
        try {
            $ast = $parser->parse($code);
            $ast = $traverser->traverse($ast);
            
            $filesData[$relativePath] = [
                'path' => $relativePath,
                'ast' => $ast,
                'code' => $code,
                'loc' => count(explode("\n", $code)),
            ];
        } catch (\Throwable $e) {
            fwrite(STDERR, "Parse error in $relativePath: " . $e->getMessage() . "\n");
        }
    }

    return $filesData;
}

echo "Parsing PHP files...\n";
$filesData = parsePhpFiles(__DIR__ . '/../src');
echo "Parsed " . count($filesData) . " PHP files\n";

/**
 * Step 2: Extract metrics from AST using a visitor
 */
class MetricsCollector extends \PhpParser\NodeVisitorAbstract
{
    public array $classes = [];
    public array $interfaces = [];
    public array $traits = [];
    public array $functions = [];
    public array $dependencies = []; // use statements, type hints, etc.
    private string $currentNamespace = '';
    private array $useStatements = [];
    // Track last added container to avoid array_merge reference issues
    private string $lastContainerType = ''; // 'classes', 'interfaces', 'traits'
    private int $lastContainerIndex = -1;
    
    public function enterNode(\PhpParser\Node $node): void
    {
        if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name->toString();
        }
        
        if ($node instanceof \PhpParser\Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $alias = $use->getAlias();
                if ($alias instanceof \PhpParser\Node\Identifier) {
                    $alias = $alias->toString();
                }
                $name = $use->name;
                $nameStr = $name instanceof \PhpParser\Node\Name ? $name->toString() : $name->toString();
                if ($alias === null) {
                    $alias = $name instanceof \PhpParser\Node\Name ? $name->getLast() : $name->toString();
                }
                $this->useStatements[$alias] = $nameStr;
            }
        }
        
        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            $this->classes[] = [
                'name' => $node->name->toString(),
                'namespace' => $this->currentNamespace,
                'fqn' => $this->currentNamespace ? $this->currentNamespace . '\\' . $node->name : $node->name->toString(),
                'type' => 'Class',
                'extends' => $node->extends ? $this->resolveType($node->extends) : null,
                'implements' => array_map([$this, 'resolveType'], $node->implements),
                'methods' => [],
                'properties' => [],
                'startLine' => $node->getStartLine(),
                'endLine' => $node->getEndLine(),
            ];
            $this->lastContainerType = 'classes';
            $this->lastContainerIndex = count($this->classes) - 1;
        }
        
        if ($node instanceof \PhpParser\Node\Stmt\Interface_) {
            $this->interfaces[] = [
                'name' => $node->name->toString(),
                'namespace' => $this->currentNamespace,
                'fqn' => $this->currentNamespace ? $this->currentNamespace . '\\' . $node->name : $node->name->toString(),
                'type' => 'Interface',
                'extends' => array_map([$this, 'resolveType'], $node->extends),
                'methods' => [],
                'startLine' => $node->getStartLine(),
                'endLine' => $node->getEndLine(),
            ];
            $this->lastContainerType = 'interfaces';
            $this->lastContainerIndex = count($this->interfaces) - 1;
        }
        
        if ($node instanceof \PhpParser\Node\Stmt\Trait_) {
            $this->traits[] = [
                'name' => $node->name->toString(),
                'namespace' => $this->currentNamespace,
                'fqn' => $this->currentNamespace ? $this->currentNamespace . '\\' . $node->name : $node->name->toString(),
                'type' => 'Trait',
                'methods' => [],
                'startLine' => $node->getStartLine(),
                'endLine' => $node->getEndLine(),
            ];
            $this->lastContainerType = 'traits';
            $this->lastContainerIndex = count($this->traits) - 1;
        }
        
        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            $ccn = $this->calculateCCN($node);
            $methodData = [
                'name' => $node->name->toString(),
                'visibility' => $node->isPublic() ? 'public' : ($node->isProtected() ? 'protected' : 'private'),
                'static' => $node->isStatic(),
                'returnType' => $node->returnType ? $this->resolveType($node->returnType) : null,
                'params' => array_map(function ($p) {
                    return [
                        'name' => $p->var->name,
                        'type' => $p->type ? $this->resolveType($p->type) : null,
                    ];
                }, $node->params),
                'ccn' => $ccn,
                'startLine' => $node->getStartLine(),
                'endLine' => $node->getEndLine(),
            ];
            
            // Add to last class/interface/trait
            $last = &$this->getLastContainer();
            if ($last) {
                $last['methods'][] = $methodData;
            }
        }
        
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $propData = [
                    'name' => $prop->name->toString(),
                    'visibility' => $node->isPublic() ? 'public' : ($node->isProtected() ? 'protected' : 'private'),
                    'static' => $node->isStatic(),
                    'type' => $node->type ? $this->resolveType($node->type) : null,
                ];
                $last = &$this->getLastContainer();
                if ($last) {
                    $last['properties'][] = $propData;
                }
            }
        }
        
        // Collect type hints from param types, return types, property types as dependencies
        if ($node instanceof \PhpParser\Node\Name) {
            $resolved = $this->resolveType($node);
            if ($resolved && !$this->isBuiltinType($resolved)) {
                $this->dependencies[] = $resolved;
            }
        }
        
        // Also handle simple identifiers (e.g., `string`, `int` in PHP 8 union types)
        if ($node instanceof \PhpParser\Node\Identifier) {
            // These are usually builtin types, but we can check
        }
    }
    
    private function &getLastContainer(): ?array
    {
        if ($this->lastContainerIndex === -1) {
            $null = null;
            return $null;
        }
        $container = &$this->{$this->lastContainerType}[$this->lastContainerIndex];
        return $container;
    }
    
    private function resolveType(\PhpParser\Node\Name|\PhpParser\Node\Identifier|\PhpParser\Node\UnionType|\PhpParser\Node\IntersectionType|\PhpParser\Node\NullableType $type): string
    {
        // Handle union types (Type1|Type2)
        if ($type instanceof \PhpParser\Node\UnionType) {
            return implode('|', array_map([$this, 'resolveType'], $type->types));
        }
        // Handle intersection types (Type1&Type2)
        if ($type instanceof \PhpParser\Node\IntersectionType) {
            return implode('&', array_map([$this, 'resolveType'], $type->types));
        }
        // Handle nullable types (?Type)
        if ($type instanceof \PhpParser\Node\NullableType) {
            return '?' . $this->resolveType($type->type);
        }
        
        $name = $type->toString();
        // Check if it's a use statement alias
        if (isset($this->useStatements[$name])) {
            return $this->useStatements[$name];
        }
        // If it's not fully qualified and we have a namespace, prepend it
        if ($this->currentNamespace && strpos($name, '\\') === false) {
            return $this->currentNamespace . '\\' . $name;
        }
        return $name;
    }
    
    private function isBuiltinType(string $type): bool
    {
        $builtins = ['string', 'int', 'float', 'bool', 'array', 'object', 'void', 'mixed', 'iterable', 'callable', 'never', 'true', 'false', 'null', 'self', 'parent', 'static'];
        return in_array(strtolower($type), $builtins);
    }
    
    private function calculateCCN(\PhpParser\Node\Stmt\ClassMethod $method): int
    {
        $ccn = 1; // Base complexity
        $traverser = new \PhpParser\NodeTraverser();
        $visitor = new class extends \PhpParser\NodeVisitorAbstract {
            public int $count = 0;
            public function enterNode(\PhpParser\Node $node): void
            {
                if ($node instanceof \PhpParser\Node\Expr\BinaryOp\BooleanOr ||
                    $node instanceof \PhpParser\Node\Expr\BinaryOp\BooleanAnd ||
                    $node instanceof \PhpParser\Node\Expr\BinaryOp\Coalesce ||
                    $node instanceof \PhpParser\Node\Stmt\If_ ||
                    $node instanceof \PhpParser\Node\Stmt\For_ ||
                    $node instanceof \PhpParser\Node\Stmt\Foreach_ ||
                    $node instanceof \PhpParser\Node\Stmt\While_ ||
                    $node instanceof \PhpParser\Node\Stmt\Do_ ||
                    $node instanceof \PhpParser\Node\Stmt\Switch_ ||
                    $node instanceof \PhpParser\Node\Stmt\Case_ ||
                    $node instanceof \PhpParser\Node\Stmt\Catch_ ||
                    $node instanceof \PhpParser\Node\Expr\Match_ ||
                    $node instanceof \PhpParser\Node\Expr\MatchArm) {
                    $this->count++;
                }
            }
        };
        $traverser->addVisitor($visitor);
        $traverser->traverse([$method]);
        return $ccn + $visitor->count;
    }
}

echo "Collecting metrics from AST...\n";
$allClasses = [];
$allInterfaces = [];
$allTraits = [];
$allDependencies = [];

foreach ($filesData as $filePath => $fileData) {
    $collector = new MetricsCollector();
    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver());
    $traverser->addVisitor($collector);
    $traverser->traverse($fileData['ast']);
    
    // Assign file path to each collected type
    foreach ($collector->classes as &$c) {
        $c['filePath'] = $filePath;
    }
    foreach ($collector->interfaces as &$i) {
        $i['filePath'] = $filePath;
    }
    foreach ($collector->traits as &$t) {
        $t['filePath'] = $filePath;
    }
    
    $allClasses[] = $collector->classes;
    $allInterfaces[] = $collector->interfaces;
    $allTraits[] = $collector->traits;
    $allDependencies[] = $collector->dependencies;
}

$allClasses = array_merge(...$allClasses);
$allInterfaces = array_merge(...$allInterfaces);
$allTraits = array_merge(...$allTraits);
$allDependencies = array_merge(...$allDependencies);

echo "Found " . count($allClasses) . " classes, " . count($allInterfaces) . " interfaces, " . count($allTraits) . " traits\n";
echo "Found " . count($allDependencies) . " dependencies\n";

/**
 * Step 3: Build CodeCharta nodes and edges structure
 */

// Group classes by namespace/package
$packages = [];
$allTypes = array_merge($allClasses, $allInterfaces, $allTraits);

foreach ($allTypes as $type) {
    $namespace = $type['namespace'] ?? '';
    $packageKey = $namespace ? str_replace('\\', '.', $namespace) : 'Global';
    // Track which file this type comes from (for later file-specific grouping)
    $type['packageKey'] = $packageKey;
    if (!isset($packages[$packageKey])) {
        $packages[$packageKey] = [
            'name' => $packageKey,
            'type' => 'Package',
            'classes' => [],
            'interfaces' => [],
            'traits' => [],
            'files' => [],
        ];
    }
    $pluralMap = ['Class' => 'classes', 'Interface' => 'interfaces', 'Trait' => 'traits'];
    $key = $pluralMap[$type['type']] ?? strtolower($type['type']) . 's';
    $packages[$packageKey][$key][] = $type;
}

// Group files by package using namespace from AST
// We need to parse namespace from each file
$fileNamespaces = [];
foreach ($filesData as $filePath => $fileData) {
    // Find namespace in AST
    $namespace = '';
    $traverser = new NodeTraverser();
    $visitor = new class extends \PhpParser\NodeVisitorAbstract {
        public string $namespace = '';
        public function enterNode(\PhpParser\Node $node): void
        {
            if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                $this->namespace = $node->name->toString();
            }
        }
    };
    $traverser->addVisitor($visitor);
    $traverser->traverse($fileData['ast']);
    $namespace = $visitor->namespace;
    
    $fileNamespaces[$filePath] = $namespace;
    
    // Determine package from namespace
    $packageKey = $namespace ? str_replace('\\', '.', $namespace) : 'Global';
    
    if (!isset($packages[$packageKey])) {
        $packages[$packageKey] = [
            'name' => $packageKey,
            'type' => 'Package',
            'classes' => [],
            'interfaces' => [],
            'traits' => [],
            'files' => [],
        ];
    }
    $packages[$packageKey]['files'][] = [
        'name' => basename($filePath),
        'path' => $filePath,
        'loc' => $fileData['loc'],
    ];
}

// Build CodeCharta nodes
$nodes = [];
$edges = [];
$nodeId = 0;
$nodeMap = []; // fqn -> node id

function addNode(&$nodes, &$nodeMap, &$nodeId, string $name, string $type, array $attributes = [], ?int $parentId = null): int
{
    $id = $nodeId++;
    $node = [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'attributes' => $attributes,
    ];
    if ($parentId !== null) {
        $node['parent'] = $parentId;
    }
    $nodes[] = $node;
    $nodeMap[$name] = $id;
    return $id;
}

// Root node
$rootId = addNode($nodes, $nodeMap, $nodeId, 'PayWire', 'Root', [
    'loc' => array_sum(array_column($filesData, 'loc')),
    'files' => count($filesData),
    'classes' => count($allClasses),
    'interfaces' => count($allInterfaces),
]);

// Package nodes
$packageIds = [];
foreach ($packages as $pkgName => $pkgData) {
    $totalLoc = array_sum(array_column($pkgData['files'], 'loc'));
    $pkgId = addNode($nodes, $nodeMap, $nodeId, $pkgName, 'Package', [
        'loc' => $totalLoc,
        'files' => count($pkgData['files']),
        'classes' => count($pkgData['classes']),
        'interfaces' => count($pkgData['interfaces']),
        'traits' => count($pkgData['traits']),
    ], $rootId);
    $packageIds[$pkgName] = $pkgId;
    
    // File nodes
    foreach ($pkgData['files'] as $file) {
        $fileId = addNode($nodes, $nodeMap, $nodeId, $file['name'], 'File', [
            'loc' => $file['loc'],
            'path' => $file['path'],
        ], $pkgId);
        
        // Class/Interface/Trait nodes under file - only those from this file
        foreach (['classes', 'interfaces', 'traits'] as $kind) {
            foreach ($pkgData[$kind] as $type) {
                // Only add types that belong to this file
                if (($type['filePath'] ?? '') !== $file['path']) {
                    continue;
                }
                
                $attrs = [
                    'methods' => count($type['methods']),
                    'properties' => count($type['properties'] ?? []),
                    'loc' => ($type['endLine'] ?? 0) - ($type['startLine'] ?? 0) + 1,
                ];
                
                // Calculate avg CCN for methods
                if (!empty($type['methods'])) {
                    $attrs['avgCcn'] = round(array_sum(array_column($type['methods'], 'ccn')) / count($type['methods']), 2);
                    $attrs['maxCcn'] = max(array_column($type['methods'], 'ccn'));
                }
                
                $typeId = addNode($nodes, $nodeMap, $nodeId, $type['name'], $type['type'], $attrs, $fileId);
                $nodeMap[$type['fqn']] = $typeId;
                
                // Method nodes
                foreach ($type['methods'] as $method) {
                    addNode($nodes, $nodeMap, $nodeId, $method['name'], 'Method', [
                        'visibility' => $method['visibility'],
                        'static' => $method['static'],
                        'ccn' => $method['ccn'],
                        'loc' => $method['endLine'] - $method['startLine'] + 1,
                    ], $typeId);
                }
            }
        }
    }
}

// Build edges (dependencies)
$depCount = array_count_values($allDependencies);
foreach ($depCount as $dep => $count) {
    if (isset($nodeMap[$dep])) {
        // Find which of our types depends on this
        // For now, just create edges from packages to external deps
    }
}

// Internal dependencies: extends, implements, uses
foreach ($allTypes as $type) {
    $fromId = $nodeMap[$type['fqn']] ?? null;
    if (!$fromId) {
        continue;
    }
    
    // extends
    $extends = $type['extends'] ?? null;
    if ($extends) {
        if (is_array($extends)) {
            foreach ($extends as $ext) {
                $toId = $nodeMap[$ext] ?? null;
                if ($toId) {
                    $edges[] = ['from' => $fromId, 'to' => $toId, 'type' => 'extends'];
                }
            }
        } else {
            $toId = $nodeMap[$extends] ?? null;
            if ($toId) {
                $edges[] = ['from' => $fromId, 'to' => $toId, 'type' => 'extends'];
            }
        }
    }
    
    // implements
    foreach ($type['implements'] ?? [] as $iface) {
        $toId = $nodeMap[$iface] ?? null;
        if ($toId) {
            $edges[] = ['from' => $fromId, 'to' => $toId, 'type' => 'implements'];
        }
    }
    
    // method parameter/return types
    foreach ($type['methods'] as $method) {
        if ($method['returnType'] && isset($nodeMap[$method['returnType']])) {
            $edges[] = ['from' => $fromId, 'to' => $nodeMap[$method['returnType']], 'type' => 'returns'];
        }
        foreach ($method['params'] as $param) {
            if ($param['type'] && isset($nodeMap[$param['type']])) {
                $edges[] = ['from' => $fromId, 'to' => $nodeMap[$param['type']], 'type' => 'uses'];
            }
        }
    }
    
    // property types
    foreach ($type['properties'] ?? [] as $prop) {
        if ($prop['type'] && isset($nodeMap[$prop['type']])) {
            $edges[] = ['from' => $fromId, 'to' => $nodeMap[$prop['type']], 'type' => 'uses'];
        }
    }
}

// Deduplicate edges
$uniqueEdges = [];
foreach ($edges as $edge) {
    $key = $edge['from'] . '->' . $edge['to'] . ':' . $edge['type'];
    if (!isset($uniqueEdges[$key])) {
        $uniqueEdges[$key] = $edge;
    }
}
$edges = array_values($uniqueEdges);

echo "Built " . count($nodes) . " nodes and " . count($edges) . " edges\n";

// Attribute types for CodeCharta
$attributeTypes = [
    'nodes' => [
        ['name' => 'loc', 'type' => 'INT'],
        ['name' => 'files', 'type' => 'INT'],
        ['name' => 'classes', 'type' => 'INT'],
        ['name' => 'interfaces', 'type' => 'INT'],
        ['name' => 'traits', 'type' => 'INT'],
        ['name' => 'methods', 'type' => 'INT'],
        ['name' => 'properties', 'type' => 'INT'],
        ['name' => 'avgCcn', 'type' => 'FLOAT'],
        ['name' => 'maxCcn', 'type' => 'INT'],
        ['name' => 'visibility', 'type' => 'STRING'],
        ['name' => 'static', 'type' => 'BOOL'],
        ['name' => 'ccn', 'type' => 'INT'],
        ['name' => 'path', 'type' => 'STRING'],
    ],
    'edges' => [
        ['name' => 'type', 'type' => 'STRING'],
    ],
];

// Final CodeCharta structure
$codecharta = [
    'nodes' => $nodes,
    'edges' => $edges,
    'attributeTypes' => $attributeTypes,
];

// Write output
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
    /** @noinspection MkdirRaceConditionInspection */
    mkdir($outputDir, 0755, true);
}
file_put_contents($outputFile, json_encode($codecharta, JSON_PRETTY_PRINT));
echo "Written CodeCharta file to: $outputFile\n";
