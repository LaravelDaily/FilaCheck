<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class DeprecatedPlaceholderRule implements Rule
{
    use CalculatesLineNumbers;
    public function name(): string
    {
        return 'deprecated-placeholder';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof StaticCall) {
            return [];
        }

        if (! $node->class instanceof Name) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'make') {
            return [];
        }

        $className = $node->class->toString();
        $shortName = $this->classBasename($className);

        if ($shortName !== 'Placeholder') {
            return [];
        }

        return [
            new Violation(
                level: 'warning',
                message: 'The `Placeholder` component is deprecated in Filament v4.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $node->name->getStartFilePos()),
                suggestion: 'Use `TextEntry::make()->state()` instead.',
            ),
        ];
    }

    private function classBasename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
