<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function rmsCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function requireCsrfToken(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    if (!$stored || !$submitted || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }
}
