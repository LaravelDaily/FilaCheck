<?php

namespace Filacheck\Support;

use Filacheck\Rules\Rule;

class RuleRegistry
{
    /** @var array<class-string<Rule>, Rule> */
    private array $rules = [];

    /**
     * Register a single rule or an array of rule classes.
     *
     * @param  class-string<Rule>|array<class-string<Rule>>  $rules
     */
    public function register(string|array $rules): self
    {
        $rules = is_array($rules) ? $rules : [$rules];

        foreach ($rules as $ruleClass) {
            if (! isset($this->rules[$ruleClass])) {
                $this->rules[$ruleClass] = new $ruleClass;
            }
        }

        return $this;
    }

    /**
     * @return Rule[]
     */
    public function all(): array
    {
        return array_values($this->rules);
    }

    /**
     * Check if a rule is registered.
     *
     * @param  class-string<Rule>  $ruleClass
     */
    public function has(string $ruleClass): bool
    {
        return isset($this->rules[$ruleClass]);
    }

    public function count(): int
    {
        return count($this->rules);
    }
}
