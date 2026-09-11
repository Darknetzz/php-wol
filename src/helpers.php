<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function env(string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    } else {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
    }

    if ($value === null) {
        return $default;
    }

    return (string) $value;
}

function load_dotenv(?string $path = null): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $path ??= dirname(__DIR__) . '/.env';
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function request_host(): string
{
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
    $host = strtolower(trim(explode(',', (string) $host)[0]));
    return explode(':', $host)[0];
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    load_dotenv();

    // Explicit override (empty string = app is at domain root).
    if (array_key_exists('BASE_PATH', $_ENV)) {
        $base = rtrim((string) $_ENV['BASE_PATH'], '/');
        return $base;
    }

    $forwardedPrefix = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '';
    if (is_string($forwardedPrefix) && $forwardedPrefix !== '') {
        $base = rtrim($forwardedPrefix, '/');
        return $base;
    }

    // Dedicated public hostname (e.g. wol.roste.org via NPM) → served at /.
    $host = request_host();
    $localHosts = ['web01', 'web01.dark.net', 'ubuntu01', 'ubuntu01.dark.net', 'localhost', '127.0.0.1', '10.0.2.55'];
    if ($host !== '' && !in_array($host, $localHosts, true)) {
        $base = '';
        return $base;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#^(.*)/public/[^/]+$#', $script, $matches)) {
        $base = $matches[1];
        return $base;
    }

    $dir = dirname($script);
    $base = ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/');

    return $base;
}

function url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = '/' . ltrim($path, '/');
    $prefix = base_path();

    if ($path === '/') {
        return $prefix !== '' ? $prefix . '/' : '/';
    }

    return $prefix . $path;
}

function data_dir(): string
{
    $dir = env('DATA_DIR', dirname(__DIR__) . '/data');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return rtrim($dir, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function render_header(string $title = 'Wake-on-LAN'): void
{
    $settings = settings_get();
    $theme = !empty($settings['DarkTheme']) ? 'dark' : 'light';
    ?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(url('/index.php')) ?>">phpwol</a>
        <?php if (auth_required() && auth_check()): ?>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/logout.php')) ?>">Log out</a>
        <?php endif; ?>
    </div>
</nav>
<main class="container pb-5">
<?php
    $flash = flash_get();
    if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif;
}

function render_footer(bool $withAppJs = false): void
{
    ?>
</main>
<script>window.WOL_BASE = <?= json_encode(base_path(), JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e(url('/assets/js/jquery.min.js')) ?>"></script>
<script src="<?= e(url('/assets/js/bootstrap.bundle.min.js')) ?>"></script>
<?php if ($withAppJs): ?>
<script src="<?= e(url('/assets/js/app.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
    <?php
}

function normalize_mac(string $mac): ?string
{
    $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac) ?? '');
    if (strlen($hex) !== 12) {
        return null;
    }

    return implode(':', str_split($hex, 2));
}

function normalize_ip(string $ip): ?string
{
    return filter_var($ip, FILTER_VALIDATE_IP) ?: null;
}
