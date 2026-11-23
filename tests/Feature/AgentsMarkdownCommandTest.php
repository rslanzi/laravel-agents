<?php

declare(strict_types=1);

it('writes a standalone AGENTS.md with every guideline', function (): void {
    $this->artisan('agents:markdown')->assertSuccessful();

    $markdown = (string) file_get_contents($this->projectFile('AGENTS.md'));

    expect($markdown)
        ->toStartWith('# AGENTS.md')
        ->toContain('## Working Agreement and Agent Boundaries')
        ->toContain('## Modular Architecture')
        ->toContain('## Documentation');
});

it('demotes headings outside code blocks only', function (): void {
    $this->artisan('agents:markdown')->assertSuccessful();

    $markdown = (string) file_get_contents($this->projectFile('AGENTS.md'));

    expect($markdown)
        ->toContain('### Working agreement (read first)')
        ->toContain("```markdown\n# ADR-NNNN: Short decision title");
});

it('refuses to overwrite an existing AGENTS.md without --force', function (): void {
    file_put_contents($this->projectFile('AGENTS.md'), 'mine');

    $this->artisan('agents:markdown')->assertFailed();

    expect(file_get_contents($this->projectFile('AGENTS.md')))->toBe('mine');
});

it('overwrites an existing AGENTS.md with --force', function (): void {
    file_put_contents($this->projectFile('AGENTS.md'), 'mine');

    $this->artisan('agents:markdown', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($this->projectFile('AGENTS.md')))->toStartWith('# AGENTS.md');
});

it('accepts an absolute path', function (): void {
    $target = $this->projectFile('docs/agents/AGENTS.md');

    $this->artisan('agents:markdown', ['--path' => $target])->assertSuccessful();

    expect($target)->toBeFile();
});

it('inlines the skills without their front matter', function (): void {
    $this->artisan('agents:markdown')->assertSuccessful();

    $markdown = (string) file_get_contents($this->projectFile('AGENTS.md'));

    expect($markdown)
        ->toContain('## Safe Database Migrations')
        ->not->toContain("\nname: safe-migrations");
});
