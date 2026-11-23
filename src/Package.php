<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents;

/**
 * Paths and content shipped by the package.
 */
final class Package
{
    public const string NAME = 'rslanzi/laravel-agents';

    /**
     * Published files, grouped so they can be installed or re-published one group at a time.
     *
     * @var array<string, list<string>>
     */
    public const array STUB_GROUPS = [
        'quality' => ['pint.json', 'rector.php', 'phpstan.neon'],
        'tests' => ['tests/Pest.php', 'tests/Unit/ArchitectureTest.php', 'tests/Unit/TranslationsTest.php'],
        'ci' => ['.github/workflows/ci.yml', '.github/pull_request_template.md'],
        'claude' => ['.claude/settings.json'],
        'docs' => ['.ai/guidelines/20-project-contexts.md', 'docs/adr/README.md', 'docs/architecture.md', 'CHANGELOG.md'],
    ];

    public static function path(string $relative = ''): string
    {
        return rtrim(dirname(__DIR__) . '/' . ltrim($relative, '/'), '/');
    }

    public static function guidelinesPath(): string
    {
        return self::path('resources/boost/guidelines');
    }

    public static function skillsPath(): string
    {
        return self::path('resources/boost/skills');
    }

    public static function stubsPath(): string
    {
        return self::path('stubs');
    }

    /**
     * Builds a standalone AGENTS.md from the header, every guideline and every skill, for projects without Boost.
     */
    public static function agentsMarkdown(): string
    {
        $sections = [rtrim((string) file_get_contents(self::path('resources/agents-md-header.md')))];

        foreach (self::sortedGlob(self::guidelinesPath() . '/*.md') as $guideline) {
            $sections[] = self::demoteHeadings((string) file_get_contents($guideline));
        }

        // Without Boost nothing loads skills on demand, so their content goes inline.
        foreach (self::sortedGlob(self::skillsPath() . '/*/SKILL.md') as $skill) {
            $sections[] = self::demoteHeadings(self::stripFrontMatter((string) file_get_contents($skill)));
        }

        return implode("\n\n", $sections) . "\n";
    }

    /**
     * @return list<string>
     */
    private static function sortedGlob(string $pattern): array
    {
        $files = glob($pattern) ?: [];
        sort($files);

        return $files;
    }

    private static function stripFrontMatter(string $markdown): string
    {
        return (string) preg_replace('/\A---\R.*?\R---\R+/s', '', $markdown);
    }

    /**
     * Shifts every Markdown heading one level down, leaving fenced code blocks (``` or ~~~) untouched.
     */
    private static function demoteHeadings(string $markdown): string
    {
        $fence = null;
        $lines = explode("\n", rtrim($markdown));

        foreach ($lines as $index => $line) {
            if (preg_match('/^(`{3,}|~{3,})/', $line, $match) === 1) {
                if ($fence === null) {
                    $fence = $match[1];
                } elseif (str_starts_with($line, $fence)) {
                    $fence = null;
                }

                continue;
            }

            if ($fence === null && str_starts_with($line, '#')) {
                $lines[$index] = '#' . $line;
            }
        }

        return implode("\n", $lines);
    }
}
