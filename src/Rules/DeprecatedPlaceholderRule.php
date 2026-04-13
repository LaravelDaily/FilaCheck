<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesClassBasename;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class DeprecatedPlaceholderRule implements ProvidesAgentFix, Rule
{
    use CalculatesLineNumbers;
    use ResolvesClassBasename;
    use ResolvesFilamentDocsUrl;

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
                suggestion: 'Use `TextEntry::make()->state()` instead. See: ' . $this->filamentDocsUrl('infolists/overview#setting-the-state-of-an-entry'),
            ),
        ];
    }

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Replace the deprecated `Placeholder::make()` component with `TextEntry::make()->state(...)`.',
            'next_steps' => [
                'Swap `Placeholder::make($name)->content($value)` for `TextEntry::make($name)->state($value)`.',
                'Add the import `use Filament\Infolists\Components\TextEntry;` if it is not already present.',
                'If the placeholder was inside a form schema rather than an infolist, move the field into the corresponding infolist `schema()` — `TextEntry` lives in the infolist namespace.',
                'This rule has no auto-fix; the rename must be done by hand.',
            ],
            'docs' => $this->filamentDocsUrl('infolists/overview#setting-the-state-of-an-entry'),
        ];
    }

}
