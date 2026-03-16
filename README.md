# FilaCheck

Static analysis for Filament v4/v5 projects. Detect deprecated patterns and code issues.

FilaCheck is like Pint but for Filament - run it after AI agents generate code or during CI to catch common issues.

## Installation

```bash
composer require laraveldaily/filacheck --dev
```

---

## Usage

You can run Filacheck as a Terminal command.

```bash
# Scan default app/Filament directory
vendor/bin/filacheck

# Scan specific directory
vendor/bin/filacheck app/Filament/Resources

# Show detailed output with categories
vendor/bin/filacheck --detailed
```

### Scan Only Dirty Files

Scan only files with uncommitted git changes, similar to Laravel Pint's `--dirty` option:

```bash
# Scan only uncommitted files
vendor/bin/filacheck --dirty

# Auto-fix only dirty files
vendor/bin/filacheck --dirty --fix

# Preview fixes for dirty files only
vendor/bin/filacheck --dirty --dry-run
```

### Auto-fixing Issues (Beta)

FilaCheck can automatically fix many issues it detects:

```bash
# Fix issues automatically
vendor/bin/filacheck --fix

# Preview suggested fixes without modifying files
vendor/bin/filacheck --dry-run

# Fix with backup files (creates .bak files before modifying)
vendor/bin/filacheck --fix --backup
```

> [!WARNING] 
> The auto-fix feature is in early stages. Always ensure your code is committed to version control (e.g., Git/GitHub) before running `--fix` so you can easily review and revert changes if needed.

---

## Configuration

You can optionally publish the config file to disable individual rules:

```bash
php artisan vendor:publish --tag=filacheck-config
```

To disable a rule, set `enabled` to `false`:

```php
// config/filacheck.php
'deprecated-reactive' => [
    'enabled' => false,
],
```

All rules are enabled by default.

---

## Available Rules (15 Free)

FilaCheck includes the following rules for detecting deprecated code patterns and common issues:

### Best Practices (2 rules)

| Rule | Description | Fixable |
|------|-------------|---------|
| `action-in-bulk-action-group` | Detects `Action::make()` inside `BulkActionGroup::make()` which should be `BulkAction::make()` | Yes |
| `wrong-tab-namespace` | Detects wrong `Tab` namespace - should be `Filament\Schemas\Components\Tabs\Tab` | Yes |

### Deprecated Code (13 rules)

| Rule | Description | Fixable |
|------|-------------|---------|
| `deprecated-reactive` | Detects `->reactive()` which should be replaced with `->live()` | Yes |
| `deprecated-action-form` | Detects `->form()` on Actions which should be `->schema()` | Yes |
| `deprecated-filter-form` | Detects `->form()` on Filters which should be `->schema()` | Yes |
| `deprecated-placeholder` | Detects `Placeholder::make()` which should be `TextEntry::make()->state()` | No |
| `deprecated-mutate-form-data-using` | Detects `->mutateFormDataUsing()` which should be `->mutateDataUsing()` | Yes |
| `deprecated-empty-label` | Detects `->label('')` which should be `->hiddenLabel()` (or `->iconButton()` on Actions) | Yes |
| `deprecated-forms-get` | Detects `use Filament\Forms\Get` or `callable $get` which should use `Filament\Schemas\Components\Utilities\Get` | Yes |
| `deprecated-forms-set` | Detects `use Filament\Forms\Set` or `callable $set` which should use `Filament\Schemas\Components\Utilities\Set` | Yes |
| `deprecated-image-column-size` | Detects `->size()` on ImageColumn which should be `->imageSize()` | Yes |
| `deprecated-view-property` | Detects `$view` property not declared as `protected string` | Yes |
| `deprecated-bulk-actions` | Detects `->bulkActions()` which should be replaced with `->toolbarActions()` | Yes |
| `deprecated-url-parameters` | Detects deprecated URL parameters like `tableFilters`, `activeTab`, `tableSearch`, etc. | Yes |
| `deprecated-test-methods` | Detects deprecated test methods like `setActionData()`, `mountTableAction()`, `assertFormSet()`, etc. | Partial |

---

## Example Output

```sh
Scanning: app/Filament

..x..x.......

deprecated-reactive (Deprecated Code)
  app/Filament/Resources/UserResource.php
    Line 45: The `reactive()` method is deprecated.
      → Use `live()` instead of `reactive()`.

deprecated-action-form (Deprecated Code)
  app/Filament/Resources/PostResource.php
    Line 78: The `form()` method is deprecated on Actions.
      → Use `schema()` instead of `form()`.

Rules: 4 passed, 2 failed
Issues: 2 warning(s)
```

---

## Exit Codes

- `0` - No violations found
- `1` - Violations found

This makes FilaCheck perfect for CI pipelines.

---

## [FilaCheck Pro](https://filamentexamples.com/filacheck)

**FilaCheck Pro** adds 19 additional rules for performance optimization, security, best practices, and UX suggestions.

### Performance Rules (4 rules)

| Rule | Description | Fixable |
|------|-------------|---------|
| `too-many-columns` | Warns when tables have more than 10 columns | No |
| `large-option-list-searchable` | Suggests `->searchable()` for lists with 10+ options | No |
| `heavy-closure-in-format-state` | Detects database queries inside `formatStateUsing()` closures that cause N+1 issues | No |
| `stats-widget-polling-not-disabled` | Warns when `StatsOverviewWidget` uses the default 5-second polling interval | Yes |

### Security Rules (2 rules)

| Rule | Description | Fixable |
|------|-------------|---------|
| `file-upload-missing-accepted-file-types` | Warns when `FileUpload` or `SpatieMediaLibraryFileUpload` is missing `acceptedFileTypes()` or `image()` | No |
| `action-missing-authorization` | Warns when `Action`, `BulkAction`, `ImportAction`, or `ExportAction` is missing `hidden()`, `visible()`, or `authorize()` | No |

### Best Practices Rules (8 rules)

| Rule | Description | Fixable |
|------|-------------|---------|
| `string-icon-instead-of-enum` | Detects string icons like `'heroicon-o-pencil'` - use `Heroicon::Pencil` enum instead | Yes |
| `string-font-weight-instead-of-enum` | Detects string font weights like `'bold'` - use `FontWeight::Bold` enum instead | Yes |
| `deprecated-notification-action-namespace` | Detects deprecated `Filament\Notifications\Actions\Action` namespace - use `Filament\Actions\Action` instead | Yes |
| `unnecessary-unique-ignore-record` | Detects `->unique(ignoreRecord: true)` which is now the default in Filament v4 | Yes |
| `custom-theme-needed` | Detects Blade files using Tailwind CSS classes without a custom Filament theme configured | No |
| `file-upload-missing-max-size` | Warns when `FileUpload` or `SpatieMediaLibraryFileUpload` is missing `maxSize()` | No |
| `bulk-action-missing-deselect` | Warns when `BulkAction` is missing `deselectRecordsAfterCompletion()` | Yes |
| `enum-missing-filament-interfaces` | Warns when enums cast in Eloquent models are missing Filament interfaces like `HasLabel` | No |

### UX Suggestions Rules (5 rules)

| Rule | Description | Fixable |
|------|-------------|---------|
| `flat-form-overload` | Warns when form schema has more than 8 fields without any layout grouping (Sections, Tabs, Fieldsets, etc.) | No |
| `relationship-select-not-searchable` | Warns when `Select` with `relationship()` is missing `searchable()` | No |
| `missing-table-filters` | Warns when table has filterable columns (boolean, badge, icon) but no filters defined | No |
| `table-without-searchable-columns` | Warns when table has text columns but none are searchable | No |
| `filter-missing-indicator` | Warns when custom `Filter` has a `schema()` but no `indicateUsing()` or `indicator()` for active filter badges | No |

Get FilaCheck Pro at [filamentexamples.com/filacheck](https://filamentexamples.com/filacheck).

---

## CI Integration

### GitHub Actions

```yaml
name: FilaCheck

on: [push, pull_request]

jobs:
  filacheck:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Run FilaCheck
        run: vendor/bin/filacheck
```

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License. See [LICENSE](LICENSE) for details.
