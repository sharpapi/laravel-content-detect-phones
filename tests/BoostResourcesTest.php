<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

$boostPath = dirname(__DIR__).'/resources/boost';

it('ships the Boost skill file', function () use ($boostPath) {
    expect($boostPath.'/skills/sharpapi-content-detect-phones/SKILL.md')->toBeFile();
});

it('gives every skill frontmatter whose name matches its folder', function () use ($boostPath) {
    $skills = glob($boostPath.'/skills/*/SKILL.md') ?: [];

    expect($skills)->not->toBeEmpty();

    foreach ($skills as $file) {
        $contents = (string) file_get_contents($file);

        expect(preg_match('/\A---\R(.*?)\R---\R/s', $contents, $matches))->toBe(1);

        if (class_exists(Yaml::class)) {
            $frontmatter = Yaml::parse($matches[1]);
        } else {
            preg_match_all('/^(\w+):\s*(.+)$/m', $matches[1], $pairs);
            $frontmatter = array_combine($pairs[1], array_map('trim', $pairs[2]));
        }

        expect($frontmatter['name'] ?? null)->toBe(basename(dirname($file)))
            ->and(trim((string) ($frontmatter['description'] ?? '')))->not->toBe('');
    }
});
