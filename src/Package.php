<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents;

/**
 * Paths and content shipped by the package.
 */
final class Package
{
    public static function path(string $relative = ''): string
    {
        return rtrim(dirname(__DIR__) . '/' . ltrim($relative, '/'), '/');
    }

    public static function guidelinesPath(): string
    {
        return self::path('resources/boost/guidelines');
    }

    public static function stubsPath(): string
    {
        return self::path('stubs');
    }

    /**
     * Builds a standalone AGENTS.md from the header and every guideline, for projects without Boost.
     */
    public static function agentsMarkdown(): string
    {
        $sections = [rtrim((string) file_get_contents(self::path('resources/agents-md-header.md')))];

        $guidelines = glob(self::guidelinesPath() . '/*.md') ?: [];
        sort($guidelines);

        foreach ($guidelines as $guideline) {
            $sections[] = self::demoteHeadings((string) file_get_contents($guideline));
        }

        return implode("\n\n", $sections) . "\n";
    }

    /**
     * Shifts every Markdown heading one level down, leaving fenced code blocks untouched.
     */
    private static function demoteHeadings(string $markdown): string
    {
        $insideFence = false;
        $lines = explode("\n", rtrim($markdown));

        foreach ($lines as $index => $line) {
            if (str_starts_with($line, '```')) {
                $insideFence = ! $insideFence;

                continue;
            }

            if (! $insideFence && str_starts_with($line, '#')) {
                $lines[$index] = '#' . $line;
            }
        }

        return implode("\n", $lines);
    }
}
