<?php
declare(strict_types=1);

session_start();

// Escape any text before printing it into HTML (prevents XSS).
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Hidden field carrying the per-session CSRF token.
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

// Reject any POST that doesn't carry this session's token (prevents CSRF).
function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';

    if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        exit('Your form expired. Go back, refresh the page, and try again.');
    }
}

function set_flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function pull_flash(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $message;
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}
