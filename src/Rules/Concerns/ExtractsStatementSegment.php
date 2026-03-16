<?php

namespace Filacheck\Rules\Concerns;

trait ExtractsStatementSegment
{
    protected function extractStatementSegment(string $code, int $startLine): string
    {
        $lines = explode("\n", $code);
        $lineOffset = 0;

        for ($i = 0; $i < $startLine - 1; $i++) {
            $lineOffset += strlen($lines[$i]) + 1;
        }

        $length = strlen($code);
        $depth = 0;
        $segment = '';

        for ($i = $lineOffset; $i < $length; $i++) {
            $char = $code[$i];

            // Skip single-quoted strings
            if ($char === "'") {
                $segment .= $char;
                $i++;
                while ($i < $length && $code[$i] !== "'") {
                    if ($code[$i] === '\\') {
                        $segment .= $code[$i];
                        $i++;
                    }
                    $segment .= $code[$i];
                    $i++;
                }
                if ($i < $length) {
                    $segment .= $code[$i];
                }

                continue;
            }

            // Skip double-quoted strings
            if ($char === '"') {
                $segment .= $char;
                $i++;
                while ($i < $length && $code[$i] !== '"') {
                    if ($code[$i] === '\\') {
                        $segment .= $code[$i];
                        $i++;
                    }
                    $segment .= $code[$i];
                    $i++;
                }
                if ($i < $length) {
                    $segment .= $code[$i];
                }

                continue;
            }

            // Skip single-line comments
            if ($char === '/' && $i + 1 < $length && $code[$i + 1] === '/') {
                while ($i < $length && $code[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            $segment .= $char;

            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
            } elseif ($char === ')' || $char === ']' || $char === '}') {
                $depth--;

                if ($depth < 0) {
                    break;
                }
            } elseif ($depth === 0 && ($char === ',' || $char === ';')) {
                break;
            }
        }

        return $segment;
    }
}
