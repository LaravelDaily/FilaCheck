<?php

namespace Filacheck\Reporting;

use Filacheck\Rules\Rule;
use Filacheck\Support\Violation;

interface ReporterInterface
{
    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     */
    public function report(array $rules, array $violations): void;

    /**
     * @param  Rule[]  $rules
     * @param  Violation[]  $violations
     * @param  array<string, mixed>  $fixResults
     */
    public function reportWithFixes(array $rules, array $violations, array $fixResults): void;
}
