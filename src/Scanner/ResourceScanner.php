<?php

namespace Filacheck\Scanner;

use Filacheck\Rules\BladeRule;
use Filacheck\Rules\Rule;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ResourceScanner
{
    private Parser $parser;

    /** @var Rule[] */
    private array $rules = [];

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    public function addRule(Rule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @return Rule[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return Violation[]
     */
    public function scan(string $directory, ?string $basePath = null): array
    {
        $violations = [];
        $files = $this->findPhpFiles($directory);

        foreach ($files as $file) {
            $fileViolations = $this->scanFile($file, $basePath);
            $violations = array_merge($violations, $fileViolations);
        }

        return $violations;
    }

    /**
     * @return Violation[]
     */
    public function scanBladeFiles(string $directory, string $basePath): array
    {
        $bladeRules = array_filter($this->rules, fn (Rule $rule) => $rule instanceof BladeRule);

        if (empty($bladeRules)) {
            return [];
        }

        $violations = [];
        $files = $this->findBladeFiles($directory);

        foreach ($files as $file) {
            $code = file_get_contents($file->getPathname());
            $context = new Context(
                file: $file->getPathname(),
                code: $code,
                basePath: $basePath,
            );

            foreach ($bladeRules as $rule) {
                $ruleViolations = $rule->checkBlade($context);
                foreach ($ruleViolations as $violation) {
                    $violation->rule = $rule->name();
                }
                $violations = array_merge($violations, $ruleViolations);
            }
        }

        return $violations;
    }

    /**
     * @return SplFileInfo[]
     */
    private function findBladeFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return SplFileInfo[]
     */
    private function findPhpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return Violation[]
     */
    private function scanFile(SplFileInfo $file, ?string $basePath = null): array
    {
        $code = file_get_contents($file->getPathname());
        $filePath = $file->getPathname();

        // Make path relative to basePath if provided
        if ($basePath !== null && str_starts_with($filePath, $basePath)) {
            $filePath = substr($filePath, strlen($basePath) + 1);
        }

        $context = new Context(
            file: $filePath,
            code: $code,
            basePath: $basePath,
        );

        try {
            $ast = $this->parser->parse($code);
        } catch (\Throwable) {
            return [];
        }

        if ($ast === null) {
            return [];
        }

        $violations = [];
        $rules = $this->rules;

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new class($rules, $context, $violations) extends NodeVisitorAbstract
        {
            public function __construct(
                private array $rules,
                private Context $context,
                private array &$violations,
            ) {}

            public function enterNode(\PhpParser\Node $node): ?int
            {
                foreach ($this->rules as $rule) {
                    $ruleViolations = $rule->check($node, $this->context);
                    foreach ($ruleViolations as $violation) {
                        $violation->rule = $rule->name();
                    }
                    $this->violations = array_merge($this->violations, $ruleViolations);
                }

                return null;
            }
        });

        $traverser->traverse($ast);

        return $violations;
    }
}
