<?php

namespace Filacheck\Rules\Concerns;

trait CalculatesLineNumbers
{
    /**
     * Calculate accurate line number from file position by counting newlines.
     */
    protected function getLineFromPosition(string $code, int $position): int
    {
        return substr_count($code, "\n", 0, $position) + 1;
    }
}
