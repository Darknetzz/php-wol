<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function auth_password(): ?string
{
    return env('APP_PASSWORD');
}

function auth_required(): bool
{
    $password = auth_password();

    return $password !== null && $password !== '';
}

function auth_check(): bool
{
    return !empty($_SESSION['authenticated']);
}

function auth_login(string $password): bool
{
    $expected = auth_password();
    if ($expected === null || $expected === '') {
        return true;
    }

    if (!hash_equals($expected, $password)) {
        return false;
    }

    $_SESSION['authenticated'] = true;
    session_regenerate_id(true);

    return true;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function auth_require(): void
{
    if (!auth_required()) {
        return;
    }

    if (auth_check()) {
        return;
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'login.php') {
        return;
    }

    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    redirect('/login.php');
}
