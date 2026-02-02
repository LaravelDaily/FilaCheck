<?php

namespace Filacheck\Rules;

/**
 * Rules implementing this interface provide auto-fix data in Violation objects.
 *
 * Fixable rules must set these properties on Violations:
 * - isFixable: true
 * - startPos: Character offset in file where fix starts
 * - endPos: Character offset in file where fix ends
 * - replacement: String to replace the matched content
 */
interface FixableRule extends Rule {}
