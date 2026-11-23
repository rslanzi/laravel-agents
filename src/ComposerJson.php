<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents;

use JsonException;
use InvalidArgumentException;

/**
 * Adds scripts to a composer.json while keeping the rest of the file byte for byte.
 */
final readonly class ComposerJson
{
    private const int FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        private string $source,
        private array $data,
    ) {}

    /**
     * @throws InvalidArgumentException when the content is not a JSON object
     */
    public static function parse(string $source): self
    {
        try {
            $data = json_decode($source, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('composer.json is not valid JSON: ' . $exception->getMessage(), previous: $exception);
        }

        if (! is_array($data) || array_is_list($data) && $data !== []) {
            throw new InvalidArgumentException('composer.json must contain a JSON object.');
        }

        /** @var array<string, mixed> $data */
        return new self($source, $data);
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return list<string>
     */
    public function packages(): array
    {
        $require = is_array($this->data['require'] ?? null) ? $this->data['require'] : [];
        $requireDev = is_array($this->data['require-dev'] ?? null) ? $this->data['require-dev'] : [];

        return array_map(strval(...), array_keys([...$require, ...$requireDev]));
    }

    public function hasScript(string $name): bool
    {
        return is_array($this->data['scripts'] ?? null) && array_key_exists($name, $this->data['scripts']);
    }

    /**
     * Returns a copy with the scripts added (or replaced). New scripts are spliced into the original text;
     * only replacing an existing script re-encodes the whole file.
     *
     * @param  array<string, mixed>  $scripts
     */
    public function withScripts(array $scripts): self
    {
        if ($scripts === []) {
            return $this;
        }

        $replacing = array_filter(array_keys($scripts), $this->hasScript(...));

        $source = $replacing === [] ? $this->spliceScripts($scripts) : $this->reencodeWithScripts($scripts);

        return self::parse($source);
    }

    /**
     * @param  array<string, mixed>  $scripts
     */
    private function reencodeWithScripts(array $scripts): string
    {
        // Objects keep empty JSON objects ({}) as objects when encoded again.
        $object = json_decode($this->source, flags: JSON_THROW_ON_ERROR);
        assert($object instanceof \stdClass);

        if (! isset($object->scripts) || ! $object->scripts instanceof \stdClass) {
            $object->scripts = new \stdClass;
        }

        foreach ($scripts as $name => $commands) {
            $object->scripts->{$name} = $commands;
        }

        return json_encode($object, self::FLAGS) . "\n";
    }

    /**
     * @param  array<string, mixed>  $scripts
     */
    private function spliceScripts(array $scripts): string
    {
        $unit = $this->indentUnit();
        [$rootClose, $scriptsOpen, $scriptsClose] = $this->locate();

        $entries = [];

        foreach ($scripts as $name => $commands) {
            $entries[] = $unit . $unit . json_encode($name, self::FLAGS) . ': ' . $this->encode($commands, $unit, 2);
        }

        if ($scriptsOpen !== null && $scriptsClose !== null) {
            $body = substr($this->source, $scriptsOpen + 1, $scriptsClose - $scriptsOpen - 1);
            $isEmpty = trim($body) === '';
            $insertAt = $isEmpty ? $scriptsOpen + 1 : $scriptsOpen + 1 + strlen(rtrim($body));
            $glue = $isEmpty ? "\n" : ",\n";

            return substr($this->source, 0, $insertAt) . $glue . implode(",\n", $entries) . "\n" . $unit
                . substr($this->source, $scriptsClose);
        }

        $beforeClose = rtrim(substr($this->source, 0, $rootClose));
        $glue = str_ends_with($beforeClose, '{') ? "\n" : ",\n";
        $block = $unit . '"scripts": {' . "\n" . implode(",\n", $entries) . "\n" . $unit . '}';

        return $beforeClose . $glue . $block . "\n" . substr($this->source, $rootClose);
    }

    private function encode(mixed $value, string $unit, int $depth): string
    {
        $lines = explode("\n", json_encode($value, self::FLAGS));

        foreach ($lines as $index => $line) {
            if ($index === 0) {
                continue;
            }

            $leading = strspn($line, ' ');
            $lines[$index] = str_repeat($unit, $depth + intdiv($leading, 4)) . substr($line, $leading);
        }

        return implode("\n", $lines);
    }

    private function indentUnit(): string
    {
        if (preg_match('/^\{\R([ \t]+)"/', $this->source, $match) === 1) {
            return $match[1];
        }

        return '    ';
    }

    /**
     * Finds the root object's closing brace and the braces of the top-level "scripts" object.
     *
     * @return array{int, ?int, ?int}
     */
    private function locate(): array
    {
        // Strings (with escapes) and structural characters, with their byte offsets.
        preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|[{}\[\],:]/', $this->source, $tokens, PREG_OFFSET_CAPTURE);

        $depth = 0;
        $previous = null;
        $key = null;
        $scriptsOpen = null;

        foreach ($tokens[0] as [$token, $offset]) {
            if ($token === ':' && $depth === 1 && $previous !== null && str_starts_with($previous, '"')) {
                $key = json_decode($previous);
            } elseif ($token === '{' || $token === '[') {
                if ($depth === 1 && $token === '{' && $key === 'scripts' && $scriptsOpen === null) {
                    $scriptsOpen = $offset;
                }

                ++$depth;
            } elseif ($token === '}' || $token === ']') {
                --$depth;

                if ($depth === 1 && $scriptsOpen !== null && $key === 'scripts') {
                    return [$this->rootClose($tokens[0], $offset), $scriptsOpen, $offset];
                }

                if ($depth === 0) {
                    return [$offset, null, null];
                }
            } elseif ($token === ',' && $depth === 1) {
                $key = null;
            }

            $previous = $token;
        }

        throw new InvalidArgumentException('composer.json has no closing brace.');
    }

    /**
     * @param  array<int, array{string, int}>  $tokens
     */
    private function rootClose(array $tokens, int $after): int
    {
        $closing = array_filter($tokens, fn (array $token): bool => $token[0] === '}' && $token[1] > $after);

        if ($closing === []) {
            throw new InvalidArgumentException('composer.json has no closing brace.');
        }

        return max(array_column($closing, 1));
    }
}
