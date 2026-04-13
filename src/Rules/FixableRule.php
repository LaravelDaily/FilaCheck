<?php

namespace Filacheck\Rules;

/**
 * Rules implementing this interface provide auto-fix data in Violation objects.
 *
 * Fixable violations come in two flavours; a violation must populate exactly one:
 *
 * Text replacement (in-place edit of the offending file):
 * - isFixable: true
 * - startPos: Character offset in file where fix starts
 * - endPos: Character offset in file where fix ends
 * - replacement: String to replace the matched content
 *
 * Command execution (run an external command from the project root, e.g. an
 * artisan generator that scaffolds files outside the offending file):
 * - isFixable: true
 * - fixCommand: Shell command to run (e.g. "php artisan make:filament-theme admin --no-interaction")
 * - fixCommandCwd: Absolute working directory to run it from (typically the
 *   scanner-supplied project basePath). CodeFixer refuses to execute when this
 *   directory does not contain an `artisan` file, as a guard against
 *   misconfigured rules.
 *
 * CodeFixer dedupes command-style violations by (fixCommand, fixCommandCwd) so
 * the same command never runs more than once per scan, regardless of how many
 * Blade files trigger it.
 */
interface FixableRule extends Rule {}
