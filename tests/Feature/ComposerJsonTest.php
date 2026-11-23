<?php

declare(strict_types=1);

use Rslanzi\LaravelAgents\ComposerJson;

it('adds a scripts object when there is none, keeping the indentation', function (): void {
    $json = ComposerJson::parse("{\n  \"name\": \"acme/app\"\n}\n")
        ->withScripts(['lint' => ['pint']]);

    expect($json->source())->toBe("{\n  \"name\": \"acme/app\",\n  \"scripts\": {\n    \"lint\": [\n      \"pint\"\n    ]\n  }\n}\n");
});

it('fills an empty scripts object', function (): void {
    $json = ComposerJson::parse("{\n    \"scripts\": {}\n}\n")
        ->withScripts(['lint' => 'pint']);

    expect($json->source())->toBe("{\n    \"scripts\": {\n        \"lint\": \"pint\"\n    }\n}\n");
});

it('ignores keys named scripts inside nested objects and strings', function (): void {
    $source = "{\n    \"extra\": {\"scripts\": {\"a\": \"b\"}},\n    \"description\": \"scripts \\\" {\"\n}\n";

    $json = ComposerJson::parse($source)->withScripts(['lint' => 'pint']);

    expect(json_decode($json->source(), true))->toBe([
        'extra' => ['scripts' => ['a' => 'b']],
        'description' => 'scripts " {',
        'scripts' => ['lint' => 'pint'],
    ]);
});

it('replaces an existing script', function (): void {
    $json = ComposerJson::parse('{"scripts": {"lint": "old"}, "autoload-dev": {}}')
        ->withScripts(['lint' => 'new']);

    expect(json_decode($json->source(), true)['scripts'])->toBe(['lint' => 'new'])
        ->and($json->source())->toContain('"autoload-dev": {}');
});

it('lists required packages', function (): void {
    $json = ComposerJson::parse('{"require": {"php": "^8.3"}, "require-dev": {"pestphp/pest": "^4.0"}}');

    expect($json->packages())->toBe(['php', 'pestphp/pest'])
        ->and($json->hasScript('lint'))->toBeFalse();
});

it('rejects JSON that is not an object', function (): void {
    ComposerJson::parse('["a"]');
})->throws(InvalidArgumentException::class, 'must contain a JSON object');
