<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

abstract class BaseDeprecatedMethodsRule implements Rule
{
    use CalculatesLineNumbers;

    /**
     * @var array<string, string>
     */
    protected array $deprecatedMethods = [];

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof MethodCall) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        $methodName = $node->name->name;

        if (! array_key_exists($methodName, $this->deprecatedMethods)) {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $replacementMethod = $this->deprecatedMethods[$methodName];

        return [
            new Violation(
                level: 'warning',
                message: "The `{$methodName}()` method is deprecated.",
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: "Use `{$replacementMethod}` instead.",
            ),
        ];
    }
}
