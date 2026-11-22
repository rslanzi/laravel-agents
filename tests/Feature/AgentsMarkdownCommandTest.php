<?php

declare(strict_types=1);

it('writes a standalone AGENTS.md with every guideline', function (): void {
    $this->artisan('agents:markdown')->assertSuccessful();

    $markdown = (string) file_get_contents($this->projectFile('AGENTS.md'));

    expect($markdown)
        ->toStartWith('# AGENTS.md')
        ->toContain('## Working Agreement and Agent Boundaries')
        ->toContain('## Modular Architecture')
        ->toContain('## Module Documentation');
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
