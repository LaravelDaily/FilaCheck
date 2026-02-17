<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;

class DeprecatedViewPropertyRule implements FixableRule
{
    use CalculatesLineNumbers;

    public function name(): string
    {
        return 'deprecated-view-property';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof Node\Stmt\Property) {
            return [];
        }

        $isViewProperty = false;
        foreach ($node->props as $prop) {
            if ($prop->name->name === 'view') {
                $isViewProperty = true;
                break;
            }
        }

        if (! $isViewProperty) {
            return [];
        }

        $isProtected = ($node->flags & Node\Stmt\Class_::MODIFIER_PROTECTED) !== 0;
        $isStatic = ($node->flags & Node\Stmt\Class_::MODIFIER_STATIC) !== 0;
        $hasStringType = $node->type instanceof Node\Identifier && $node->type->name === 'string';

        if ($isProtected && ! $isStatic && $hasStringType) {
            return [];
        }

        $startPos = $node->getStartFilePos();
        $propVarStartPos = $node->props[0]->getStartFilePos();

        return [
            new Violation(
                level: 'warning',
                message: 'The `$view` property must be declared as `protected string`.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Change the declaration to `protected string $view`.',
                isFixable: true,
                startPos: $startPos,
                endPos: $propVarStartPos,
                replacement: 'protected string ',
            ),
        ];
    }
}
