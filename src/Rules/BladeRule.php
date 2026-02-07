<?php

namespace Filacheck\Rules;

use Filacheck\Support\Context;
use Filacheck\Support\Violation;

interface BladeRule extends Rule
{
    /**
     * @return Violation[]
     */
    public function checkBlade(Context $context): array;
}
