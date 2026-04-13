<?php

namespace Filacheck\Rules;

use Filacheck\Support\Violation;

/**
 * Optional rule extension. Rules implementing this interface can return
 * a structured, agent-specific fix description for each violation they
 * emit.
 *
 * Used by structured reporters (e.g. JsonReporter when an AI coding
 * agent is detected) to give the agent a concrete, actionable plan
 * instead of the human-readable suggestion. Has no effect on text
 * reporters and is independent from FixableRule — a rule can be
 * auto-fixable, agent-fix-providing, both, or neither.
 *
 * Implementations should return any JSON-serializable value (string,
 * array, scalar, etc.) or null. Returning null is equivalent to not
 * implementing the interface — the JSON output will fall back to
 * `fix: null`.
 */
interface ProvidesAgentFix
{
    /**
     * @return mixed JSON-serializable fix data, or null
     */
    public function agentFix(Violation $violation): mixed;
}
