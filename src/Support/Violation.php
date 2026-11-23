<?php

namespace Filacheck\Support;

class Violation
{
    /**
     * @param  non-empty-list<string>|null  $fixCommand  Executable followed by literal arguments.
     */
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
        public bool $silent = false,
        public ?array $fixCommand = null,
        public ?string $fixCommandCwd = null,
    ) {
        if ($fixCommand !== null) {
            if (! array_is_list($fixCommand) || $fixCommand === [] || $fixCommand[0] === '') {
                throw new \InvalidArgumentException('Fix commands must be a non-empty list with an executable.');
            }

            foreach ($fixCommand as $argument) {
                if (! is_string($argument) || str_contains($argument, "\0")) {
                    throw new \InvalidArgumentException('Fix command arguments must be strings without null bytes.');
                }
            }
        }
    }
}
