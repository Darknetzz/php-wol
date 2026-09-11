<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function settings_path(): string
{
    return data_dir() . '/settings.json';
}

function settings_defaults(): array
{
    return [
        'DarkTheme' => true,
        'VerbosePing' => false,
    ];
}

function settings_get(): array
{
    $path = settings_path();
    if (!is_file($path)) {
        settings_save(settings_defaults());
    }

    $json = json_decode((string) file_get_contents($path), true);
    if (!is_array($json)) {
        return settings_defaults();
    }

    return array_merge(settings_defaults(), $json);
}

function settings_save(array $settings): void
{
    file_put_contents(
        settings_path(),
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );
}

function settings_toggle(string $key, bool $value): void
{
    $settings = settings_get();
    $settings[$key] = $value;
    settings_save($settings);
}
