<?php

namespace Filacheck\Rules\Concerns;

trait ResolvesClassBasename
{
    protected function classBasename(string $class): string
    {
        $parts = explode('\\', $class);

        return end($parts);
    }
}
