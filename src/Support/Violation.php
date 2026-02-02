<?php

namespace Filacheck\Support;

class Violation
{
    public function __construct(
        public string $level,
        public string $message,
        public string $file,
        public int $line,
        public ?string $suggestion = null,
        public ?string $rule = null,
        public bool $isFixable = false,
        public ?int $startPos = null,
        public ?int $endPos = null,
        public ?string $replacement = null,
    ) {}
}
