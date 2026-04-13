<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

class DeprecatedReactiveRule implements FixableRule, ProvidesAgentFix
{
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;
    public function name(): string
    {
        return 'deprecated-reactive';
    }

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

        if ($node->name->name !== 'reactive') {
            return [];
        }

        $nameNode = $node->name;
        $startPos = $nameNode->getStartFilePos();
        $endPos = $nameNode->getEndFilePos() + 1;

        return [
            new Violation(
                level: 'warning',
                message: 'The `reactive()` method is deprecated.',
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $startPos),
                suggestion: 'Use `live()` instead of `reactive()`. See: ' . $this->filamentDocsUrl('forms/overview#the-basics-of-reactivity'),
                isFixable: true,
                startPos: $startPos,
                endPos: $endPos,
                replacement: 'live',
            ),
        ];
    }

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Replace the deprecated `reactive()` modifier with `live()`.',
            'next_steps' => [
                'Rename `->reactive()` to `->live()` on the field.',
                'If you need debounced updates, use `->live(debounce: 500)`. If you only want to react on blur, use `->live(onBlur: true)`.',
            ],
            'docs' => $this->filamentDocsUrl('forms/overview#the-basics-of-reactivity'),
        ];
    }
}
