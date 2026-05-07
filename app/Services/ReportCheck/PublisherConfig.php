<?php

namespace App\Services\ReportCheck;

use InvalidArgumentException;

class PublisherConfig
{
    /** @param array<string,string> $rules */
    public function __construct(
        public readonly string $slug,
        public readonly array $rules,
    ) {}

    public static function load(string $slug): self
    {
        $all = config('report_publishers', []);
        if (!isset($all[$slug])) {
            throw new InvalidArgumentException("Unknown publisher slug: {$slug}");
        }
        return new self($slug, $all[$slug]);
    }

    public function rule(string $key): string
    {
        if (!isset($this->rules[$key])) {
            throw new InvalidArgumentException("Missing publisher rule '{$key}' for {$this->slug}");
        }
        return $this->rules[$key];
    }

    public function displayName(): string
    {
        return $this->rule('display_name');
    }
}
