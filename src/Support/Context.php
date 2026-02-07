<?php

namespace Filacheck\Support;

class Context
{
    public function __construct(
        public string $file,
        public string $code,
        public ?string $basePath = null,
    ) {}
}
