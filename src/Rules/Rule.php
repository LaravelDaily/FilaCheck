<?php

namespace Filacheck\Rules;

use Filacheck\Enums\RuleCategory;
use Filacheck\Support\Context;
use Filacheck\Support\Violation;
use PhpParser\Node;

interface Rule
{
    public function name(): string;

    public function category(): RuleCategory;

    /**
     * @return Violation[]
     */
    public function check(Node $node, Context $context): array;
}
