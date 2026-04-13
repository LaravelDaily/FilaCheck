<?php

namespace Filacheck\Reporting;

use Closure;
use Symfony\Component\Console\Output\OutputInterface;

final class ReporterFactory
{
    private static ?Closure $resolver = null;

    /**
     * Register a custom reporter factory.
     *
     * The closure receives ($output, $verbose) and must return a
     * ReporterInterface. Used by FilaCheck-Pro to install JsonReporter
     * when an AI agent is detected at runtime.
     */
    public static function override(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function reset(): void
    {
        self::$resolver = null;
    }

    public static function make(OutputInterface $output, bool $verbose = false): ReporterInterface
    {
        if (self::$resolver !== null) {
            $reporter = (self::$resolver)($output, $verbose);

            if ($reporter instanceof ReporterInterface) {
                return $reporter;
            }
        }

        return new StandaloneReporter($output, $verbose);
    }
}
