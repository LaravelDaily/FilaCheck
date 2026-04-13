<?php

namespace Filacheck\Support;

/**
 * Process-wide flags about how filacheck is being invoked.
 *
 * Used so override packages (e.g. FilaCheck-Pro) can signal "we're running
 * inside an AI coding agent" to bin/filacheck without the free package
 * gaining a dependency on agent-detection logic. The free CLI reads this
 * flag to decide whether to force --fix mode (so CodeFixer applies all
 * auto-fixable issues with byte precision before any structured reporter
 * — like JsonReporter — emits output).
 */
final class RunContext
{
    private static bool $agentMode = false;

    public static function markAgent(): void
    {
        self::$agentMode = true;
    }

    public static function isAgent(): bool
    {
        return self::$agentMode;
    }

    public static function reset(): void
    {
        self::$agentMode = false;
    }
}
