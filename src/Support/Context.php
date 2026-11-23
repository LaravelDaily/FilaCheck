<?php

namespace Filacheck\Support;

class Context
{
    /**
     * `$imports` holds the file's class imports, keyed by lowercased alias.
     * `startPos`/`endPos` point at the imported name of a plain `use`
     * statement and are null for group imports, which can't be renamed in place.
     *
     * @param  array<string, array{name: string, alias: string, startPos: ?int, endPos: ?int}>  $imports
     */
    public function __construct(
        public string $file,
        public string $code,
        public ?string $basePath = null,
        public array $imports = [],
    ) {}
}
