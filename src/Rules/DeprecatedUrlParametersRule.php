<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Rules\Concerns\CalculatesLineNumbers;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;

class DeprecatedUrlParametersRule implements FixableRule
{
    use CalculatesLineNumbers;

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
}
