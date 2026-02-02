<?php

use Filacheck\Rules\DeprecatedPlaceholderRule;

it('detects Placeholder component usage', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\Placeholder;

class TestResource
{
    public function form(): array
    {
        return [
            Placeholder::make('created_at')
                ->content(fn ($record) => $record->created_at),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedPlaceholderRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('Placeholder', $violations);
});

it('passes when TextEntry is used', function () {
    $code = <<<'PHP'
<?php

use Filament\Infolists\Components\TextEntry;

class TestResource
{
    public function form(): array
    {
        return [
            TextEntry::make('created_at')
                ->state(fn ($record) => $record->created_at),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedPlaceholderRule, $code);

    $this->assertNoViolations($violations);
});

it('detects fully qualified Placeholder', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function form(): array
    {
        return [
            \Filament\Forms\Components\Placeholder::make('info'),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedPlaceholderRule, $code);

    $this->assertViolationCount(1, $violations);
});
