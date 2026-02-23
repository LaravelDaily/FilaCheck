<?php

use Filacheck\Rules\DeprecatedImageColumnSizeRule;

it('detects size method usage', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\ImageColumn;

class TestResource
{
    public function table(): array
    {
        return [
            ImageColumn::make('thumbnail_url')
                ->label('Thumbnail')
                ->circular()
                ->size(50),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('size()', $violations);
});

it('passes when imageSize is used instead of size', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\ImageColumn;

class TestResource
{
    public function table(): array
    {
        return [
            ImageColumn::make('thumbnail_url')
                ->label('Thumbnail')
                ->circular()
                ->imageSize(50),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple size usages', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function table(): array
    {
        return [
            ImageColumn::make('avatar')->size(40),
            ImageColumn::make('thumbnail')->size(50),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertViolationCount(2, $violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function table(): array
    {
        return [
            ImageColumn::make('thumbnail')->size(50),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes size to imageSize', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function table(): array
    {
        return [
            ImageColumn::make('thumbnail')->size(50),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedImageColumnSizeRule, $code);

    expect($fixedCode)->toContain('->imageSize(50)');
    expect($fixedCode)->not->toContain('->size(50)');
});

it('does not flag size on non-ImageColumn components', function () {
    $code = <<<'PHP'
<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class TestResource
{
    public function form(): array
    {
        return [
            TextInput::make('name')->size('lg'),
            Select::make('status')->size('sm'),
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes multiple size usages', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function table(): array
    {
        return [
            ImageColumn::make('avatar')->size(40),
            ImageColumn::make('thumbnail')->size(50),
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedImageColumnSizeRule, $code);

    expect(substr_count($fixedCode, '->imageSize('))->toBe(2);
    expect($fixedCode)->not->toContain('->size(');
});

it('detects $this->size(50) in a class extending ImageColumn', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\ImageColumn;

class ThumbnailColumn extends ImageColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->size(50);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('size()', $violations);
});

it('detects $this->circular()->size(50) chained in a class extending ImageColumn', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\ImageColumn;

class ThumbnailColumn extends ImageColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->circular()
            ->size(50);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('size()', $violations);
});

it('ignores $this->size(50) in a class that does not extend ImageColumn', function () {
    $code = <<<'PHP'
<?php

class SomeComponent extends Component
{
    protected function setUp(): void
    {
        $this->size(50);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedImageColumnSizeRule, $code);

    $this->assertNoViolations($violations);
});

it('fixes $this->size() to $this->imageSize() in custom ImageColumn classes', function () {
    $code = <<<'PHP'
<?php

use Filament\Tables\Columns\ImageColumn;

class ThumbnailColumn extends ImageColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->size(50);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedImageColumnSizeRule, $code);

    expect($fixedCode)->toContain('$this->imageSize(50)');
    expect($fixedCode)->not->toContain('$this->size(50)');
});
