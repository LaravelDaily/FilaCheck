<?php

namespace Filacheck\Enums;

enum RuleCategory: string
{
    case Deprecated = 'deprecated';
    case Performance = 'performance';
    case BestPractices = 'best-practices';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Deprecated => 'Deprecated Code',
            self::Performance => 'Performance',
            self::BestPractices => 'Best Practices',
            self::Security => 'Security',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Deprecated => 'Methods and patterns that are deprecated in Filament v4/v5',
            self::Performance => 'Rules that help identify potential performance issues',
            self::BestPractices => 'Recommendations for cleaner and more maintainable code',
            self::Security => 'Rules that help identify potential security issues',
        };
    }
}
