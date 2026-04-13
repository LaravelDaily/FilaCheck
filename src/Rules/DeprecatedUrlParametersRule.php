<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Rules\Concerns\ResolvesFilamentDocsUrl;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

class DeprecatedUrlParametersRule implements FixableRule, ProvidesAgentFix
{
    use CalculatesLineNumbers;
    use ResolvesFilamentDocsUrl;

    private const RENAMED_PARAMETERS = [
        'activeRelationManager' => 'relation',
        'activeTab' => 'tab',
        'isTableReordering' => 'reordering',
        'tableFilters' => 'filters',
        'tableGrouping' => 'grouping',
        'tableGroupingDirection' => 'groupingDirection',
        'tableSearch' => 'search',
        'tableSort' => 'sort',
    ];

    public function name(): string
    {
        return 'deprecated-url-parameters';
    }

    public function category(): RuleCategory
    {
        return RuleCategory::Deprecated;
    }

    public function check(Node $node, Context $context): array
    {
        if (! $node instanceof String_) {
            return [];
        }

        $violations = [];
        $stringStart = $node->getStartFilePos();
        $stringEnd = $node->getEndFilePos();
        $rawString = substr($context->code, $stringStart, $stringEnd - $stringStart + 1);

        foreach (self::RENAMED_PARAMETERS as $old => $new) {
            if (! str_contains($node->value, $old)) {
                continue;
            }

            $offset = strpos($rawString, $old);
            $absoluteStart = $stringStart + $offset;
            $absoluteEnd = $absoluteStart + strlen($old);

            $violations[] = new Violation(
                level: 'warning',
                message: "The `{$old}` URL parameter is deprecated.",
                file: $context->file,
                line: $this->getLineFromPosition($context->code, $absoluteStart),
                suggestion: "Use `{$new}` instead of `{$old}`.",
                isFixable: true,
                startPos: $absoluteStart,
                endPos: $absoluteEnd,
                replacement: $new,
            );
        }

        return $violations;
    }

    public function agentFix(Violation $violation): mixed
    {
        return [
            'instructions' => 'Rename the deprecated Livewire URL query-string parameter to its v4 equivalent.',
            'next_steps' => [
                'Rename the parameter inside the string literal: `activeRelationManager`→`relation`, `activeTab`→`tab`, `isTableReordering`→`reordering`, `tableFilters`→`filters`, `tableGrouping`→`grouping`, `tableGroupingDirection`→`groupingDirection`, `tableSearch`→`search`, `tableSort`→`sort`.',
                'These parameter names also appear in the `#[Url]` attribute and `$queryString` arrays on Livewire components — keep the surrounding code intact, just swap the key.',
                'Each occurrence is reported as its own violation so apply the fix to every match in the file.',
            ],
            'docs' => $this->filamentDocsUrl('resources/overview'),
        ];
    }
}
