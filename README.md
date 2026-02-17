# FilaCheck

Static analysis for Filament v4/v5 projects. Detect deprecated patterns and code issues.

FilaCheck is like Pint but for Filament - run it after AI agents generate code or during CI to catch common issues.

## Installation

```bash
composer require laraveldaily/filacheck --dev
```

---

## Usage

You can run Filacheck as a Terminal or Artisan command.

### Standalone

```bash
# Scan default app/Filament directory
vendor/bin/filacheck

# Scan specific directory
vendor/bin/filacheck app/Filament/Resources

# Show detailed output with categories
vendor/bin/filacheck --detailed
```

### Laravel Artisan Command

```bash
php artisan filacheck
php artisan filacheck app/Filament/Resources
php artisan filacheck --detailed

php artisan filacheck --fix
php artisan filacheck --fix --backup
```

### Auto-fixing Issues (Beta)

FilaCheck can automatically fix many issues it detects:

```bash
# Fix issues automatically
vendor/bin/filacheck --fix

# Fix with backup files (creates .bak files before modifying)
vendor/bin/filacheck --fix --backup
```

> [!WARNING] 
> The auto-fix feature is in early stages. Always ensure your code is committed to version control (e.g., Git/GitHub) before running `--fix` so you can easily review and revert changes if needed.

---

## Available Rules (Free)

FilaCheck includes the following rules for detecting deprecated code patterns:

### Deprecated Code

| Rule | Description | Fixable |
|------|-------------|---------|
| `deprecated-reactive` | Detects `->reactive()` which should be replaced with `->live()` | Yes |
| `deprecated-action-form` | Detects `->form()` on Actions which should be `->schema()` | Yes |
| `deprecated-filter-form` | Detects `->form()` on Filters which should be `->schema()` | Yes |
| `deprecated-placeholder` | Detects `Placeholder::make()` which should be `TextEntry::make()->state()` | No |
| `deprecated-mutate-form-data-using` | Detects `->mutateFormDataUsing()` which should be `->mutateDataUsing()` | Yes |
| `deprecated-empty-label` | Detects `->label('')` which should be `->hiddenLabel()` (or `->iconButton()` on Actions) | Yes |
| `deprecated-forms-set` | Detects `use Filament\Forms\Set` which should be `use Filament\Schemas\Components\Utilities\Set` | Yes |
| `deprecated-image-column-size` | Detects `->size()` on ImageColumn which should be `->imageSize()` | Yes |
| `deprecated-view-property` | Detects `$view` property not declared as `protected string` | Yes |

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

**FilaCheck Pro** adds 9 additional rules for performance optimization and best practices.

### Performance Rules

| Rule | Description |
|------|-------------|
| `too-many-columns` | Warns when tables have more than 10 columns |
| `table-defer-loading` | Suggests adding `->deferLoading()` to tables |
| `table-missing-eager-loading` | Detects relationship columns without eager loading |
| `large-option-list-searchable` | Suggests `->searchable()` for lists with 10+ options |

### Best Practices Rules

| Rule | Description |
|------|-------------|
| `string-icon-instead-of-enum` | Detects string icons like `'heroicon-o-pencil'` - use `Heroicon::Pencil` enum instead (auto-fixable) |
| `string-font-weight-instead-of-enum` | Detects string font weights like `'bold'` - use `FontWeight::Bold` enum instead (auto-fixable) |
| `deprecated-notification-action-namespace` | Detects old `Filament\Notifications\Actions\Action` namespace - use `Filament\Actions\Action` instead (auto-fixable) |
| `unnecessary-unique-ignore-record` | Detects `->unique()->ignoreRecord(true)` which is now the default in Filament v4 (auto-fixable) |
| `custom-theme-needed` | Detects Tailwind CSS usage in Blade files without a custom Filament theme configured |

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
