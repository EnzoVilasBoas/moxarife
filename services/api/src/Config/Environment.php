<?php

declare(strict_types=1);

namespace Moxarife\Api\Config;

use RuntimeException;

final class Environment
{
    /** @var array<string, string> */
    private array $values;

    /** @param array<string, string> $values */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException('Arquivo de ambiente não encontrado.');
        }

        $values = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return new self($values);
    }

    public static function fromSources(string $path): self
    {
        $values = [];
        if (is_file($path)) {
            $values = self::fromFile($path)->values;
        }

        foreach ([$_ENV, $_SERVER, getenv()] as $source) {
            if (!is_array($source)) {
                continue;
            }

            foreach ($source as $key => $value) {
                if (is_string($key) && is_scalar($value)) {
                    $values[$key] = (string) $value;
                }
            }
        }

        return new self($values);
    }

    public function get(string $key, ?string $default = null): string
    {
        $value = array_key_exists($key, $this->values) ? $this->values[$key] : $default;
        if ($value === null) {
            throw new RuntimeException(sprintf('Variável obrigatória ausente: %s', $key));
        }

        return $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->values[$key] ?? ($default ? 'true' : 'false');
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public function int(string $key, int $default): int
    {
        $value = $this->values[$key] ?? (string) $default;
        if (!is_numeric($value)) {
            throw new RuntimeException(sprintf('Variável inválida: %s', $key));
        }

        return (int) $value;
    }
}
