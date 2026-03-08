<?php

namespace Filacheck\Reporting\Concerns;

use Symfony\Component\Console\Formatter\OutputFormatter;

trait HandlesDryRunPreviews
{
    private bool $isDryRun = false;

    /** @var array<string, array<int, array{line: int, column: int, from: string, to: string}>> */
    private array $previewsByFile = [];

    /** @var array<string, array<int, bool>> */
    private array $consumedPreviewIndexes = [];

    /** @var array<string, array<int, string>> */
    private array $fileLinesCache = [];

    protected function initializeDryRunPreviewState(array $fixResults): void
    {
        $this->isDryRun = (bool) ($fixResults['dryRun'] ?? false);
        $this->previewsByFile = $fixResults['previews'] ?? [];
        $this->consumedPreviewIndexes = [];
        $this->fileLinesCache = [];
    }

    /**
     * @return array<int, array{line: int, column: int, from: string, to: string}>
     */
    protected function consumePreviewChanges(string $file, int $line): array
    {
        $allFileChanges = $this->previewsByFile[$file] ?? [];
        $consumedIndexes = $this->consumedPreviewIndexes[$file] ?? [];
        $changes = [];

        foreach ($allFileChanges as $index => $change) {
            if (($consumedIndexes[$index] ?? false) || $change['line'] > $line) {
                continue;
            }

            $changes[] = $change;
            $consumedIndexes[$index] = true;
        }

        $this->consumedPreviewIndexes[$file] = $consumedIndexes;

        return $changes;
    }

    /**
     * @return array<int, array{line: int, column: int, from: string, to: string}>
     */
    protected function consumeRemainingPreviewChanges(string $file): array
    {
        $allFileChanges = $this->previewsByFile[$file] ?? [];
        $consumedIndexes = $this->consumedPreviewIndexes[$file] ?? [];
        $remainingChanges = [];

        foreach ($allFileChanges as $index => $change) {
            if ($consumedIndexes[$index] ?? false) {
                continue;
            }

            $remainingChanges[] = $change;
            $consumedIndexes[$index] = true;
        }

        $this->consumedPreviewIndexes[$file] = $consumedIndexes;

        return $remainingChanges;
    }

    protected function getFileLine(string $file, int $lineNumber): ?string
    {
        if ($lineNumber < 1) {
            return null;
        }

        if (! array_key_exists($file, $this->fileLinesCache)) {
            $lines = @file($file, FILE_IGNORE_NEW_LINES);
            $this->fileLinesCache[$file] = $lines === false ? [] : $lines;
        }

        return $this->fileLinesCache[$file][$lineNumber - 1] ?? null;
    }

    /**
     * @param  array{line: int, column: int, from: string, to: string}  $change
     */
    protected function renderDryRunDiffLines(string $file, array $change): void
    {
        $currentLine = $this->getFileLine($file, $change['line']) ?? '';
        $trimmedCurrentLine = ltrim($currentLine, " \t");
        $leadingWhitespaceLength = strlen($currentLine) - strlen($trimmedCurrentLine);
        $column = max(1, $change['column'] - $leadingWhitespaceLength);
        $from = $change['from'];
        $to = $change['to'];

        if (str_contains($from, "\n") || str_contains($to, "\n")) {
            $this->renderMultilineDryRunDiffLines($trimmedCurrentLine, $column, $from, $to);

            return;
        }

        if ($from !== '') {
            $oldLine = $this->colorizeLineWithHighlight($trimmedCurrentLine, $column, $from, 'red');
            $this->output->writeln("        <fg=red>-</> {$oldLine}");
        }

        if ($to === '') {
            return;
        }

        if ($from === '' && str_contains($to, "\n")) {
            $addedLines = preg_split('/\r?\n/', rtrim($to, "\r\n")) ?: [];

            foreach ($addedLines as $addedLine) {
                $this->output->writeln('        <fg=green>+</> <fg=green>' . OutputFormatter::escape(ltrim($addedLine, " \t")) . '</>');
            }

            return;
        }

        $newLine = $this->buildNewLine($trimmedCurrentLine, $column, $from, $to);
        $newLine = $this->colorizeLineWithHighlight($newLine, $column, $to, 'green');
        $this->output->writeln("        <fg=green>+</> {$newLine}");
    }

    private function renderMultilineDryRunDiffLines(string $currentLine, int $column, string $from, string $to): void
    {
        $prefix = substr($currentLine, 0, max(0, $column - 1));
        $fromLines = $this->prepareMultilinePreviewLines($from, $prefix);
        $toLines = $this->prepareMultilinePreviewLines($to, $prefix);

        foreach ($fromLines as $line) {
            $this->output->writeln('        <fg=red>-</> <fg=red>' . OutputFormatter::escape($line) . '</>');
        }

        foreach ($toLines as $line) {
            $this->output->writeln('        <fg=green>+</> <fg=green>' . OutputFormatter::escape($line) . '</>');
        }
    }

    /**
     * @return array<int, string>
     */
    private function prepareMultilinePreviewLines(string $content, string $prefix): array
    {
        if ($content === '') {
            return [];
        }

        $lines = preg_split('/\r?\n/', rtrim($content, "\r\n")) ?: [];

        if ($lines === []) {
            return [];
        }

        $lines[0] = $prefix . $lines[0];

        return $lines;
    }

    private function buildNewLine(string $line, int $column, string $from, string $to): string
    {
        $offset = max(0, $column - 1);

        if ($from === '') {
            return substr($line, 0, $offset) . $to . substr($line, $offset);
        }

        if (substr($line, $offset, strlen($from)) === $from) {
            return substr_replace($line, $to, $offset, strlen($from));
        }

        $fallbackOffset = strpos($line, $from);
        if ($fallbackOffset === false) {
            return $line;
        }

        return substr_replace($line, $to, $fallbackOffset, strlen($from));
    }

    private function colorizeLineWithHighlight(string $line, int $column, string $segment, string $changeColor): string
    {
        if ($segment === '') {
            return '<fg=gray>' . OutputFormatter::escape($line) . '</>';
        }

        $offset = max(0, $column - 1);

        if (substr($line, $offset, strlen($segment)) !== $segment) {
            $fallbackOffset = strpos($line, $segment);
            if ($fallbackOffset === false) {
                return '<fg=gray>' . OutputFormatter::escape($line) . '</>';
            }

            $offset = $fallbackOffset;
        }

        $before = substr($line, 0, $offset);
        $highlighted = substr($line, $offset, strlen($segment));
        $after = substr($line, $offset + strlen($segment));

        return '<fg=gray>' . OutputFormatter::escape($before) . '</>'
            . "<fg={$changeColor}>" . OutputFormatter::escape($highlighted) . '</>'
            . '<fg=gray>' . OutputFormatter::escape($after) . '</>';
    }
}
