/**
 * Auth Kit — Node.js Backend (Express)
 *
 * Run:   npm install express && node server-node.js
 * Open:  http://localhost:3000
 *
 * This is a MOCK server with fake data so you can preview all templates.
 * Replace the mock logic with real database calls for production.
 */

const express = require('express');
const path = require('path');
const app = express();
const PORT = 3000;

app.use(express.json());
app.use(express.static(path.join(__dirname, '..')));

// ─── Mock data ───

const USERS = [
    { id: '1', username: 'admin', password: 'admin123', email: 'admin@example.com', display_name: 'Admin', role: 'admin', status: 'active', created_at: '2026-01-15T10:00:00Z', has_2fa: false },
    { id: '2', username: 'demo', password: 'demo123', email: 'demo@example.com', display_name: 'Demo User', role: 'user', status: 'active', created_at: '2026-02-20T14:30:00Z', has_2fa: false },
    { id: '3', username: 'viewer', password: 'view123', email: 'viewer@example.com', display_name: 'Viewer', role: 'viewer', status: 'inactive', created_at: '2026-03-01T09:00:00Z', has_2fa: false },
];

const SESSIONS = [
    { id: 's1', device: 'MacBook Pro', browser: 'Chrome 120', ip: '192.168.1.10', location: 'Amsterdam, NL', last_active: new Date().toISOString(), current: true },
    { id: 's2', device: 'iPhone 15', browser: 'Safari', ip: '10.0.0.5', location: 'Rotterdam, NL', last_active: new Date(Date.now() - 3600000).toISOString(), current: false },
];

let failCounts = {};

// ─── Auth ───

app.post('/api/auth/login', (req, res) => {
    const { username, password } = req.body;
    const ip = req.ip;

    // Rate limit mock
    failCounts[ip] = failCounts[ip] || 0;
    if (failCounts[ip] >= 10) {
        return res.status(429).json({ error: 'Too many attempts', retry_after: 30 });
    }

    const user = USERS.find(u => u.username === username && u.password === password);
    if (!user) {
        failCounts[ip]++;
        return res.status(401).json({ error: 'Invalid credentials' });
    }

    failCounts[ip] = 0;
    res.json({ user: { id: user.id, username: user.username, email: user.email, role: user.role } });
});

app.post('/api/auth/verify-2fa', (req, res) => {
    const { code } = req.body;
    if (code === '123456') {
        res.json({ user: { id: '1', username: 'admin', email: 'admin@example.com' } });
    } else {
        res.status(401).json({ error: 'Invalid code' });
    }
});

app.post('/api/hq/login', (req, res) => {
    const { username, password, totpCode } = req.body;
    const user = USERS.find(u => u.username === username && u.password === password);
    if (!user) return res.status(401).json({ error: 'Invalid credentials' });
    if (totpCode !== '123456') return res.status(401).json({ error: 'Invalid 2FA code' });
    res.json({ user: { id: user.id, username: user.username } });
});

app.post('/api/auth/register', (req, res) => {
    const { username, email } = req.body;
    if (USERS.find(u => u.username === username)) {
        return res.status(409).json({ error: 'Username already taken' });
    }
    if (USERS.find(u => u.email === email)) {
        return res.status(409).json({ error: 'Email already registered' });
    }
    res.json({ user: { id: String(USERS.length + 1), username, email } });
});

app.post('/api/auth/forgot-password', (req, res) => {
    // Always return 200 — no enumeration
    res.json({ message: 'If that email exists, we sent a reset code.' });
});

app.post('/api/auth/verify-reset', (req, res) => {
    const { code } = req.body;
    if (code === '123456') {
        res.json({ reset_token: 'mock_reset_token_abc' });
    } else {
        res.status(400).json({ error: 'Invalid code' });
    }
});

app.post('/api/auth/reset-password', (req, res) => {
    res.json({ message: 'Password updated successfully' });
});

app.post('/api/auth/verify-email', (req, res) => {
    res.json({ message: 'Email verified' });
});

app.post('/api/auth/resend-verify', (req, res) => {
    res.json({ message: 'Verification email sent' });
});

app.post('/api/auth/logout', (req, res) => {
    res.json({ message: 'Logged out' });
});

// ─── Account ───

app.get('/api/auth/profile', (req, res) => {
    res.json({ username: 'demo', display_name: 'Demo User', email: 'demo@example.com', avatar_url: null });
});

app.patch('/api/auth/profile', (req, res) => {
    res.json({ message: 'Profile updated' });
});

app.post('/api/auth/profile/avatar', (req, res) => {
    res.json({ avatar_url: '/avatar.png' });
});

app.post('/api/auth/change-password', (req, res) => {
    const { current_password } = req.body;
    if (current_password !== 'demo123') {
        return res.status(401).json({ error: 'Current password is incorrect' });
    }
    res.json({ message: 'Password changed' });
});

app.post('/api/auth/delete-account', (req, res) => {
    res.json({ message: 'Account deleted' });
});

// ─── Sessions ───

app.get('/api/auth/sessions', (req, res) => {
    res.json({ sessions: SESSIONS });
});

app.post('/api/auth/sessions/revoke', (req, res) => {
    res.json({ message: 'Session revoked' });
});

app.post('/api/auth/sessions/revoke-all', (req, res) => {
    res.json({ message: 'All sessions revoked' });
});

// ─── 2FA ───

app.get('/api/auth/2fa/status', (req, res) => {
    res.json({ enabled: false });
});

app.post('/api/auth/2fa/setup', (req, res) => {
    res.json({
        qr_url: 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/AuthKit:demo?secret=JBSWY3DPEHPK3PXP',
        manual_key: 'JBSWY3DPEHPK3PXP',
        pending_secret: 'mock_secret'
    });
});

app.post('/api/auth/2fa/confirm', (req, res) => {
    const { code } = req.body;
    if (code === '123456') {
        res.json({ backup_codes: ['ABCD1234', 'EFGH5678', 'IJKL9012', 'MNOP3456', 'QRST7890', 'UVWX1234'] });
    } else {
        res.status(400).json({ error: 'Invalid code' });
    }
});

app.post('/api/auth/2fa/disable', (req, res) => {
    res.json({ message: '2FA disabled' });
});

// ─── Settings ───

app.get('/api/auth/settings', (req, res) => {
    res.json({ theme: 'dark', language: 'en', notifications: { email: true, push: true, marketing: false } });
});

app.patch('/api/auth/settings', (req, res) => {
    res.json({ message: 'Settings updated' });
});

// ─── Team / Invite ───

app.get('/api/team/members', (req, res) => {
    res.json({
        members: [
            { id: '1', name: 'Admin', email: 'admin@example.com', role: 'owner', joined_at: '2026-01-15T10:00:00Z' },
            { id: '2', name: 'Demo User', email: 'demo@example.com', role: 'member', joined_at: '2026-02-20T14:30:00Z' },
        ],
        pending: [
            { id: 'inv1', email: 'new@example.com', role: 'member', invited_at: '2026-03-28T12:00:00Z' },
        ]
    });
});

app.post('/api/team/invite', (req, res) => {
    res.json({ message: 'Invite sent' });
});

app.post('/api/team/invite/cancel', (req, res) => {
    res.json({ message: 'Invite cancelled' });
});

app.post('/api/team/members/remove', (req, res) => {
    res.json({ message: 'Member removed' });
});

// ─── Support ───

app.post('/api/support/ticket', (req, res) => {
    res.json({ message: 'Ticket created', ticket_id: 'TK-' + Math.random().toString(36).slice(2, 8).toUpperCase() });
});

// ─── Onboarding ───

app.post('/api/auth/onboarding', (req, res) => {
    res.json({ message: 'Onboarding complete' });
});

// ─── Super registration 2FA (alternate paths) ───

app.post('/api/auth/setup-2fa', (req, res) => {
    res.json({
        qr_url: 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/AuthKit:demo?secret=JBSWY3DPEHPK3PXP',
        manual_key: 'JBSWY3DPEHPK3PXP',
        pending_secret: 'mock_secret'
    });
});

app.post('/api/auth/confirm-2fa', (req, res) => {
    const { code } = req.body;
    if (code === '123456') {
        res.json({ backup_codes: ['ABCD1234', 'EFGH5678', 'IJKL9012', 'MNOP3456', 'QRST7890', 'UVWX1234'] });
    } else {
        res.status(400).json({ error: 'Invalid code' });
    }
});

// ─── Super forgot-password 2FA ───

app.post('/api/auth/verify-reset-2fa', (req, res) => {
    const { code, backup_code } = req.body;
    if (code === '123456' || backup_code) {
        res.json({ reset_token: 'mock_reset_token_2fa' });
    } else {
        res.status(401).json({ error: 'Invalid code' });
    }
});

// ─── Search ───

app.get('/api/search', (req, res) => {
    const q = (req.query.q || '').toLowerCase();
    const items = [
        { id: '1', title: 'Getting Started Guide', description: 'Learn how to set up your account', url: '/docs/getting-started', type: 'page', date: '2026-03-15T10:00:00Z', author: 'Admin' },
        { id: '2', title: 'API Documentation', description: 'Full API reference', url: '/docs/api', type: 'page', date: '2026-03-10T14:00:00Z', author: 'Admin' },
        { id: '3', title: 'Dashboard Overview', description: 'Monitor your usage and performance', url: '/dashboard', type: 'page', date: '2026-03-01T09:00:00Z' },
        { id: '4', title: 'Billing Settings', description: 'Manage your subscription and invoices', url: '/settings/billing', type: 'settings', date: '2026-02-20T11:00:00Z' },
        { id: '5', title: 'Team Members', description: 'Add and manage team members', url: '/settings/team', type: 'settings' },
        { id: '6', title: 'Release Notes v2.1', description: 'New features and bug fixes', url: '/blog/release-2-1', type: 'post', date: '2026-03-20T16:00:00Z', author: 'Dev Team' },
        { id: '7', title: 'Security Best Practices', description: 'How to keep your account safe', url: '/blog/security', type: 'post', date: '2026-02-14T10:00:00Z', author: 'Security' },
        { id: '8', title: 'Demo User', description: 'demo@example.com', url: '/admin/users/2', type: 'user' },
        { id: '9', title: 'Admin', description: 'admin@example.com', url: '/admin/users/1', type: 'user' },
        { id: '10', title: 'Upload Guidelines', description: 'Accepted file types and size limits', url: '/docs/uploads', type: 'page' },
    ];

    const filter = req.query.filter || req.query.scope;
    let results = items;
    if (q) results = results.filter(r => r.title.toLowerCase().includes(q) || (r.description || '').toLowerCase().includes(q));
    if (filter && filter !== 'all') results = results.filter(r => r.type === filter || r.type === filter.replace(/s$/, ''));

    res.json({ results, total: results.length });
});

// ─── Users (data table) ───

app.get('/api/users', (req, res) => {
    const q = (req.query.q || '').toLowerCase();
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 25;

    let results = USERS.map(u => ({ id: u.id, name: u.display_name, email: u.email, role: u.role, status: u.status, created_at: u.created_at }));

    if (q) results = results.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
    if (req.query.status) results = results.filter(u => u.status === req.query.status);
    if (req.query.role) results = results.filter(u => u.role === req.query.role);

    const total = results.length;
    const pages = Math.ceil(total / limit);
    results = results.slice((page - 1) * limit, page * limit);

    res.json({ results, total, page, pages });
});

// ─── Payments ───

app.post('/api/payments/create-intent', (req, res) => {
    res.json({ client_secret: 'pi_mock_secret_123' });
});

app.post('/api/payments/create', (req, res) => {
    // Mollie would return a checkout URL
    res.json({ checkout_url: '/payments/success.html?payment_id=mock_123' });
});

app.post('/api/payments/apply-coupon', (req, res) => {
    const { coupon_code } = req.body;
    if (coupon_code.toUpperCase() === 'DEMO50') {
        res.json({ valid: true, discount_amount: 500, new_total: 499 });
    } else {
        res.json({ valid: false, error: 'Invalid coupon code' });
    }
});

app.get('/api/payments/:id', (req, res) => {
    res.json({
        plan: 'Pro Plan — Monthly',
        amount: '9.99',
        currency: 'EUR',
        method: 'iDEAL',
        transaction_id: 'tr_' + req.params.id,
        date: new Date().toISOString()
    });
});

// ─── Waitlist ───

app.post('/api/waitlist', (req, res) => {
    res.json({ message: "You're on the list!" });
});

// ─── Start ───

app.listen(PORT, () => {
    console.log(`\n  Auth Kit running at http://localhost:${PORT}`);
    console.log(`  Browse templates at http://localhost:${PORT}/index.html`);
    console.log(`\n  Mock credentials: admin/admin123 or demo/demo123`);
    console.log(`  Mock 2FA code: 123456`);
    console.log(`  Mock coupon: DEMO50\n`);
});
