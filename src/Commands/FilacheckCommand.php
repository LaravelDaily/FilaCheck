<?php

namespace Filacheck\Commands;

use Filacheck\Fixer\CodeFixer;
use Filacheck\Reporting\ConsoleReporter;
use Filacheck\Rules\BladeRule;
use Filacheck\Scanner\ResourceScanner;
use Filacheck\Support\RuleRegistry;
use Illuminate\Console\Command;

class FilacheckCommand extends Command
{
    protected $signature = 'filacheck
        {path? : Path to scan for Filament resources}
        {--detailed : Show detailed output with rule categories}
        {--fix : Automatically fix issues where possible}
        {--backup : Create backup files when fixing (requires --fix)}';

    protected $description = 'Run static analysis on Filament resources';

    public function handle(RuleRegistry $registry): int
    {
        $path = $this->argument('path') ?? app_path('Filament');
        $fix = $this->option('fix');
        $backup = $this->option('backup');

        $this->line("Scanning: {$path}");
        $this->newLine();

        $scanner = new ResourceScanner;

        foreach ($registry->all() as $rule) {
            $scanner->addRule($rule);
        }

        $violations = $scanner->scan($path, base_path());

        $hasBladeRules = array_filter($scanner->getRules(), fn ($rule) => $rule instanceof BladeRule);

        if (! empty($hasBladeRules)) {
            $bladeViolations = $scanner->scanBladeFiles(resource_path('views/filament'), base_path());
            $violations = array_merge($violations, $bladeViolations);
        }

        $reporter = new ConsoleReporter($this, $this->option('detailed'));

        if ($fix && count($violations) > 0) {
            $fixer = new CodeFixer;
            $fixResults = $fixer->fix($violations, $backup);

            $reporter->reportWithFixes($scanner->getRules(), $violations, $fixResults);

            return $fixResults['skipped'] > 0 ? Command::FAILURE : Command::SUCCESS;
        }

        $reporter->report($scanner->getRules(), $violations);

        return count($violations) > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
