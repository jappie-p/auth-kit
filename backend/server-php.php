<?php
/**
 * Auth Kit — PHP Backend (no framework, no composer)
 *
 * Run:   php -S localhost:3000 server-php.php
 * Open:  http://localhost:3000
 *
 * Works on ANY server with PHP 7+ (school servers, shared hosting, XAMPP, etc.)
 * This is a MOCK server. Replace with real DB queries for production.
 */

// ─── Config ───

$ROOT = dirname(__DIR__); // serves from auth-kit/

// ─── Mock data ───

$USERS = [
    ['id' => '1', 'username' => 'admin', 'password' => 'admin123', 'email' => 'admin@example.com', 'display_name' => 'Admin', 'role' => 'admin', 'status' => 'active', 'created_at' => '2026-01-15T10:00:00Z'],
    ['id' => '2', 'username' => 'demo', 'password' => 'demo123', 'email' => 'demo@example.com', 'display_name' => 'Demo User', 'role' => 'user', 'status' => 'active', 'created_at' => '2026-02-20T14:30:00Z'],
    ['id' => '3', 'username' => 'viewer', 'password' => 'view123', 'email' => 'viewer@example.com', 'display_name' => 'Viewer', 'role' => 'viewer', 'status' => 'inactive', 'created_at' => '2026-03-01T09:00:00Z'],
];

// ─── Router ───

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$body = json_decode(file_get_contents('php://input'), true) ?: [];

header('Content-Type: application/json');

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function find_user($users, $field, $value) {
    foreach ($users as $u) {
        if ($u[$field] === $value) return $u;
    }
    return null;
}

// ─── API Routes ───

// Login
if ($method === 'POST' && $uri === '/api/auth/login') {
    $username = $body['username'] ?? '';
    $password = $body['password'] ?? '';

    foreach ($USERS as $u) {
        if ($u['username'] === $username && $u['password'] === $password) {
            json_out(['user' => ['id' => $u['id'], 'username' => $u['username'], 'email' => $u['email'], 'role' => $u['role']]]);
        }
    }
    json_out(['error' => 'Invalid credentials'], 401);
}

// HQ Login (super tier)
if ($method === 'POST' && $uri === '/api/hq/login') {
    $username = $body['username'] ?? '';
    $password = $body['password'] ?? '';
    $totp = $body['totpCode'] ?? '';

    foreach ($USERS as $u) {
        if ($u['username'] === $username && $u['password'] === $password) {
            if ($totp !== '123456') json_out(['error' => 'Invalid 2FA code'], 401);
            json_out(['user' => ['id' => $u['id'], 'username' => $u['username']]]);
        }
    }
    json_out(['error' => 'Invalid credentials'], 401);
}

// Register
if ($method === 'POST' && $uri === '/api/auth/register') {
    $username = $body['username'] ?? '';
    $email = $body['email'] ?? '';

    foreach ($USERS as $u) {
        if ($u['username'] === $username) json_out(['error' => 'Username already taken'], 409);
        if ($u['email'] === $email) json_out(['error' => 'Email already registered'], 409);
    }
    json_out(['user' => ['id' => '99', 'username' => $username, 'email' => $email]]);
}

// Forgot password
if ($method === 'POST' && $uri === '/api/auth/forgot-password') {
    json_out(['message' => 'If that email exists, we sent a reset code.']);
}

// Verify reset code
if ($method === 'POST' && $uri === '/api/auth/verify-reset') {
    $code = $body['code'] ?? '';
    if ($code === '123456') json_out(['reset_token' => 'mock_reset_token_abc']);
    json_out(['error' => 'Invalid code'], 400);
}

// Reset password
if ($method === 'POST' && $uri === '/api/auth/reset-password') {
    json_out(['message' => 'Password updated successfully']);
}

// Verify email
if ($method === 'POST' && $uri === '/api/auth/verify-email') {
    json_out(['message' => 'Email verified']);
}

// Resend verify
if ($method === 'POST' && $uri === '/api/auth/resend-verify') {
    json_out(['message' => 'Verification email sent']);
}

// 2FA verify
if ($method === 'POST' && $uri === '/api/auth/verify-2fa') {
    $code = $body['code'] ?? '';
    if ($code === '123456') json_out(['user' => ['id' => '1', 'username' => 'admin']]);
    json_out(['error' => 'Invalid code'], 401);
}

// Logout
if ($method === 'POST' && $uri === '/api/auth/logout') {
    json_out(['message' => 'Logged out']);
}

// ─── Account ───

if ($method === 'GET' && $uri === '/api/auth/profile') {
    json_out(['username' => 'demo', 'display_name' => 'Demo User', 'email' => 'demo@example.com', 'avatar_url' => null]);
}

if ($method === 'PATCH' && $uri === '/api/auth/profile') {
    json_out(['message' => 'Profile updated']);
}

if ($method === 'POST' && $uri === '/api/auth/change-password') {
    $current = $body['current_password'] ?? '';
    if ($current !== 'demo123') json_out(['error' => 'Current password is incorrect'], 401);
    json_out(['message' => 'Password changed']);
}

if ($method === 'POST' && $uri === '/api/auth/delete-account') {
    json_out(['message' => 'Account deleted']);
}

// ─── Sessions ───

if ($method === 'GET' && $uri === '/api/auth/sessions') {
    json_out(['sessions' => [
        ['id' => 's1', 'device' => 'Desktop', 'browser' => 'Chrome', 'ip' => '192.168.1.10', 'location' => 'Amsterdam', 'last_active' => date('c'), 'current' => true],
        ['id' => 's2', 'device' => 'iPhone', 'browser' => 'Safari', 'ip' => '10.0.0.5', 'location' => 'Rotterdam', 'last_active' => date('c', time() - 3600), 'current' => false],
    ]]);
}

if ($method === 'POST' && $uri === '/api/auth/sessions/revoke') {
    json_out(['message' => 'Session revoked']);
}

if ($method === 'POST' && $uri === '/api/auth/sessions/revoke-all') {
    json_out(['message' => 'All sessions revoked']);
}

// ─── 2FA Setup ───

if ($method === 'GET' && $uri === '/api/auth/2fa/status') {
    json_out(['enabled' => false]);
}

if ($method === 'POST' && $uri === '/api/auth/2fa/setup') {
    json_out([
        'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/AuthKit:demo?secret=JBSWY3DPEHPK3PXP',
        'manual_key' => 'JBSWY3DPEHPK3PXP',
        'pending_secret' => 'mock_secret'
    ]);
}

if ($method === 'POST' && $uri === '/api/auth/2fa/confirm') {
    $code = $body['code'] ?? '';
    if ($code === '123456') json_out(['backup_codes' => ['ABCD1234', 'EFGH5678', 'IJKL9012', 'MNOP3456', 'QRST7890', 'UVWX1234']]);
    json_out(['error' => 'Invalid code'], 400);
}

if ($method === 'POST' && $uri === '/api/auth/2fa/disable') {
    json_out(['message' => '2FA disabled']);
}

// ─── Settings ───

if ($method === 'GET' && $uri === '/api/auth/settings') {
    json_out(['theme' => 'dark', 'language' => 'en', 'notifications' => ['email' => true, 'push' => true, 'marketing' => false]]);
}

if ($method === 'PATCH' && $uri === '/api/auth/settings') {
    json_out(['message' => 'Settings updated']);
}

// ─── Team / Invite ───

if ($method === 'GET' && $uri === '/api/team/members') {
    json_out([
        'members' => [
            ['id' => '1', 'name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'owner', 'joined_at' => '2026-01-15T10:00:00Z'],
            ['id' => '2', 'name' => 'Demo User', 'email' => 'demo@example.com', 'role' => 'member', 'joined_at' => '2026-02-20T14:30:00Z'],
        ],
        'pending' => [
            ['id' => 'inv1', 'email' => 'new@example.com', 'role' => 'member', 'invited_at' => '2026-03-28T12:00:00Z'],
        ]
    ]);
}

if ($method === 'POST' && $uri === '/api/team/invite') {
    json_out(['message' => 'Invite sent']);
}

if ($method === 'POST' && $uri === '/api/team/invite/cancel') {
    json_out(['message' => 'Invite cancelled']);
}

if ($method === 'POST' && $uri === '/api/team/members/remove') {
    json_out(['message' => 'Member removed']);
}

// ─── Support ───

if ($method === 'POST' && $uri === '/api/support/ticket') {
    json_out(['message' => 'Ticket created', 'ticket_id' => 'TK-' . strtoupper(substr(md5(rand()), 0, 6))]);
}

// ─── Onboarding ───

if ($method === 'POST' && $uri === '/api/auth/onboarding') {
    json_out(['message' => 'Onboarding complete']);
}

// ─── Super registration 2FA (alternate paths) ───

if ($method === 'POST' && $uri === '/api/auth/setup-2fa') {
    json_out([
        'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/AuthKit:demo?secret=JBSWY3DPEHPK3PXP',
        'manual_key' => 'JBSWY3DPEHPK3PXP',
        'pending_secret' => 'mock_secret'
    ]);
}

if ($method === 'POST' && $uri === '/api/auth/confirm-2fa') {
    $code = $body['code'] ?? '';
    if ($code === '123456') json_out(['backup_codes' => ['ABCD1234', 'EFGH5678', 'IJKL9012', 'MNOP3456', 'QRST7890', 'UVWX1234']]);
    json_out(['error' => 'Invalid code'], 400);
}

// ─── Super forgot-password 2FA ───

if ($method === 'POST' && $uri === '/api/auth/verify-reset-2fa') {
    $code = $body['code'] ?? '';
    $backup = $body['backup_code'] ?? '';
    if ($code === '123456' || $backup) json_out(['reset_token' => 'mock_reset_token_2fa']);
    json_out(['error' => 'Invalid code'], 401);
}

// ─── Avatar upload ───

if ($method === 'POST' && $uri === '/api/auth/profile/avatar') {
    json_out(['avatar_url' => '/avatar.png']);
}

// ─── Search ───

if ($method === 'GET' && $uri === '/api/search') {
    $q = strtolower($_GET['q'] ?? '');
    $items = [
        ['id' => '1', 'title' => 'Getting Started Guide', 'description' => 'Learn how to set up your account', 'url' => '/docs/getting-started', 'type' => 'page'],
        ['id' => '2', 'title' => 'API Documentation', 'description' => 'Full API reference', 'url' => '/docs/api', 'type' => 'page'],
        ['id' => '3', 'title' => 'Dashboard Overview', 'description' => 'Monitor your usage', 'url' => '/dashboard', 'type' => 'page'],
        ['id' => '4', 'title' => 'Billing Settings', 'description' => 'Manage subscription', 'url' => '/settings/billing', 'type' => 'settings'],
        ['id' => '5', 'title' => 'Team Members', 'description' => 'Manage team', 'url' => '/settings/team', 'type' => 'settings'],
        ['id' => '6', 'title' => 'Demo User', 'description' => 'demo@example.com', 'url' => '/admin/users/2', 'type' => 'user'],
    ];

    $results = array_values(array_filter($items, function($item) use ($q) {
        if (!$q) return true;
        return strpos(strtolower($item['title']), $q) !== false || strpos(strtolower($item['description']), $q) !== false;
    }));

    json_out(['results' => $results, 'total' => count($results)]);
}

// ─── Users (data table) ───

if ($method === 'GET' && $uri === '/api/users') {
    $q = strtolower($_GET['q'] ?? '');
    $results = array_map(function($u) {
        return ['id' => $u['id'], 'name' => $u['display_name'], 'email' => $u['email'], 'role' => $u['role'], 'status' => $u['status'], 'created_at' => $u['created_at']];
    }, $USERS);

    if ($q) {
        $results = array_values(array_filter($results, function($u) use ($q) {
            return strpos(strtolower($u['name']), $q) !== false || strpos(strtolower($u['email']), $q) !== false;
        }));
    }

    json_out(['results' => $results, 'total' => count($results), 'page' => 1, 'pages' => 1]);
}

// ─── Payments ───

if ($method === 'POST' && $uri === '/api/payments/create-intent') {
    json_out(['client_secret' => 'pi_mock_secret_123']);
}

if ($method === 'POST' && $uri === '/api/payments/create') {
    json_out(['checkout_url' => '/payments/success.html?payment_id=mock_123']);
}

if ($method === 'POST' && $uri === '/api/payments/apply-coupon') {
    $code = strtoupper($body['coupon_code'] ?? '');
    if ($code === 'DEMO50') json_out(['valid' => true, 'discount_amount' => 500, 'new_total' => 499]);
    json_out(['valid' => false, 'error' => 'Invalid coupon code']);
}

if ($method === 'GET' && preg_match('#^/api/payments/(.+)$#', $uri, $m)) {
    json_out([
        'plan' => 'Pro Plan — Monthly',
        'amount' => '9.99',
        'currency' => 'EUR',
        'method' => 'iDEAL',
        'transaction_id' => 'tr_' . $m[1],
        'date' => date('c')
    ]);
}

// ─── Waitlist ───

if ($method === 'POST' && $uri === '/api/waitlist') {
    json_out(['message' => "You're on the list!"]);
}

// ─── Static files fallback ───

// If no API route matched, serve static files
$file = $ROOT . $uri;
if ($uri === '/') $file = $ROOT . '/index.html';

if (is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $types = ['html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    readfile($file);
    exit;
}

// 404
http_response_code(404);
echo json_encode(['error' => 'Not found']);
