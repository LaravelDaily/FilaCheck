<?php

use Filacheck\Rules\DeprecatedUrlParametersRule;

it('detects tableFilters in url() method', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrl(): string
    {
        return $this->url().'?tableFilters[support_id][value]=123';
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedUrlParametersRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('tableFilters', $violations);
});

it('detects activeRelationManager in getUrl() context', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function redirectToResource(): string
    {
        return Resource::getUrl('edit', ['record' => $id, 'activeRelationManager' => 'posts']);
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedUrlParametersRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('activeRelationManager', $violations);
});

it('detects activeTab in query string', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrl(): string
    {
        return $this->url().'?activeTab=settings';
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedUrlParametersRule, $code);

    $this->assertViolationCount(1, $violations);
    $this->assertViolationContains('activeTab', $violations);
});

it('passes when new parameter names are used', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrls(): array
    {
        return [
            $this->url().'?filters[support_id][value]=123',
            Resource::getUrl('edit', ['record' => $id, 'relation' => 'posts']),
            $this->url().'?tab=settings',
            $this->url().'?reordering=true',
            $this->url().'?grouping=category',
            $this->url().'?groupingDirection=asc',
            $this->url().'?search=test',
            $this->url().'?sort=name',
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedUrlParametersRule, $code);

    $this->assertNoViolations($violations);
});

it('detects multiple different deprecated parameters in same file', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrls(): array
    {
        return [
            $this->url().'?tableFilters[support_id][value]=123',
            $this->url().'?tableSearch=test',
            $this->url().'?activeTab=settings',
        ];
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedUrlParametersRule, $code);

    $this->assertViolationCount(3, $violations);
});

it('marks violations as fixable', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrl(): string
    {
        return $this->url().'?tableFilters[support_id][value]=123';
    }
}
PHP;

    $violations = $this->scanCode(new DeprecatedUrlParametersRule, $code);

    $this->assertViolationIsFixable($violations);
});

it('fixes tableFilters to filters', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrl(): string
    {
        return $this->url().'?tableFilters[support_id][value]=123';
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedUrlParametersRule, $code);

    expect($fixedCode)->toContain('filters[support_id]');
    expect($fixedCode)->not->toContain('tableFilters[');
});

it('fixes activeRelationManager to relation', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function redirectToResource(): string
    {
        return Resource::getUrl('edit', ['record' => $id, 'activeRelationManager' => 'posts']);
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedUrlParametersRule, $code);

    expect($fixedCode)->toContain("'relation' =>");
    expect($fixedCode)->not->toContain('activeRelationManager');
});

it('fixes multiple different deprecated parameters', function () {
    $code = <<<'PHP'
<?php

class TestResource
{
    public function getUrls(): array
    {
        return [
            $this->url().'?tableFilters[support_id][value]=123',
            $this->url().'?tableSearch=test',
            $this->url().'?activeTab=settings',
        ];
    }
}
PHP;

    $fixedCode = $this->scanAndFix(new DeprecatedUrlParametersRule, $code);

    expect($fixedCode)->toContain('filters[support_id]');
    expect($fixedCode)->toContain('search=test');
    expect($fixedCode)->toContain('tab=settings');
    expect($fixedCode)->not->toContain('tableFilters');
    expect($fixedCode)->not->toContain('tableSearch');
    expect($fixedCode)->not->toContain('activeTab');
});
