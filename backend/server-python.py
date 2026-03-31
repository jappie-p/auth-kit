"""
Auth Kit — Python Backend (zero dependencies)

Run:   python3 server-python.py
Open:  http://localhost:3000

Uses only Python standard library — no pip, no Flask, no Django.
Works anywhere Python 3.6+ is installed (school servers, Raspberry Pi, etc.)
This is a MOCK server. Replace with real DB for production.
"""

import json
import os
import re
from http.server import HTTPServer, SimpleHTTPRequestHandler
from urllib.parse import urlparse, parse_qs

PORT = 3000
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

USERS = [
    {"id": "1", "username": "admin", "password": "admin123", "email": "admin@example.com", "display_name": "Admin", "role": "admin", "status": "active", "created_at": "2026-01-15T10:00:00Z"},
    {"id": "2", "username": "demo", "password": "demo123", "email": "demo@example.com", "display_name": "Demo User", "role": "user", "status": "active", "created_at": "2026-02-20T14:30:00Z"},
    {"id": "3", "username": "viewer", "password": "view123", "email": "viewer@example.com", "display_name": "Viewer", "role": "viewer", "status": "inactive", "created_at": "2026-03-01T09:00:00Z"},
]

SEARCH_ITEMS = [
    {"id": "1", "title": "Getting Started Guide", "description": "Learn how to set up your account", "url": "/docs/getting-started", "type": "page"},
    {"id": "2", "title": "API Documentation", "description": "Full API reference", "url": "/docs/api", "type": "page"},
    {"id": "3", "title": "Dashboard Overview", "description": "Monitor your usage", "url": "/dashboard", "type": "page"},
    {"id": "4", "title": "Billing Settings", "description": "Manage subscription", "url": "/settings/billing", "type": "settings"},
    {"id": "5", "title": "Demo User", "description": "demo@example.com", "url": "/admin/users/2", "type": "user"},
]


class AuthKitHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=ROOT, **kwargs)

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path
        params = parse_qs(parsed.query)

        if path == "/api/auth/profile":
            return self.json_response({"username": "demo", "display_name": "Demo User", "email": "demo@example.com", "avatar_url": None})

        if path == "/api/auth/sessions":
            return self.json_response({"sessions": [
                {"id": "s1", "device": "Desktop", "browser": "Chrome", "ip": "192.168.1.10", "location": "Amsterdam", "last_active": "2026-03-29T12:00:00Z", "current": True},
                {"id": "s2", "device": "iPhone", "browser": "Safari", "ip": "10.0.0.5", "location": "Rotterdam", "last_active": "2026-03-29T11:00:00Z", "current": False},
            ]})

        if path == "/api/auth/2fa/status":
            return self.json_response({"enabled": False})

        if path == "/api/auth/settings":
            return self.json_response({"theme": "dark", "language": "en", "notifications": {"email": True, "push": True, "marketing": False}})

        if path == "/api/team/members":
            return self.json_response({
                "members": [
                    {"id": "1", "name": "Admin", "email": "admin@example.com", "role": "owner", "joined_at": "2026-01-15T10:00:00Z"},
                    {"id": "2", "name": "Demo User", "email": "demo@example.com", "role": "member", "joined_at": "2026-02-20T14:30:00Z"},
                ],
                "pending": [
                    {"id": "inv1", "email": "new@example.com", "role": "member", "invited_at": "2026-03-28T12:00:00Z"},
                ]
            })

        if path == "/api/search":
            q = params.get("q", [""])[0].lower()
            results = [i for i in SEARCH_ITEMS if q in i["title"].lower() or q in i["description"].lower()] if q else SEARCH_ITEMS
            return self.json_response({"results": results, "total": len(results)})

        if path == "/api/users":
            q = params.get("q", [""])[0].lower()
            results = [{"id": u["id"], "name": u["display_name"], "email": u["email"], "role": u["role"], "status": u["status"], "created_at": u["created_at"]} for u in USERS]
            if q:
                results = [u for u in results if q in u["name"].lower() or q in u["email"].lower()]
            return self.json_response({"results": results, "total": len(results), "page": 1, "pages": 1})

        if re.match(r"^/api/payments/.+$", path):
            pid = path.split("/")[-1]
            return self.json_response({"plan": "Pro Plan", "amount": "9.99", "currency": "EUR", "method": "iDEAL", "transaction_id": "tr_" + pid, "date": "2026-03-29T12:00:00Z"})

        # Serve static files
        super().do_GET()

    def do_POST(self):
        body = self.read_body()
        path = urlparse(self.path).path

        if path == "/api/auth/login":
            user = next((u for u in USERS if u["username"] == body.get("username") and u["password"] == body.get("password")), None)
            if not user:
                return self.json_response({"error": "Invalid credentials"}, 401)
            return self.json_response({"user": {"id": user["id"], "username": user["username"], "email": user["email"], "role": user["role"]}})

        if path == "/api/hq/login":
            user = next((u for u in USERS if u["username"] == body.get("username") and u["password"] == body.get("password")), None)
            if not user:
                return self.json_response({"error": "Invalid credentials"}, 401)
            if body.get("totpCode") != "123456":
                return self.json_response({"error": "Invalid 2FA code"}, 401)
            return self.json_response({"user": {"id": user["id"], "username": user["username"]}})

        if path == "/api/auth/register":
            username = body.get("username", "")
            if any(u["username"] == username for u in USERS):
                return self.json_response({"error": "Username already taken"}, 409)
            return self.json_response({"user": {"id": "99", "username": username, "email": body.get("email")}})

        if path == "/api/auth/forgot-password":
            return self.json_response({"message": "If that email exists, we sent a reset code."})

        if path == "/api/auth/verify-reset":
            if body.get("code") == "123456":
                return self.json_response({"reset_token": "mock_reset_token_abc"})
            return self.json_response({"error": "Invalid code"}, 400)

        if path in ["/api/auth/reset-password", "/api/auth/verify-email", "/api/auth/resend-verify", "/api/auth/logout"]:
            return self.json_response({"message": "OK"})

        if path == "/api/auth/verify-2fa":
            if body.get("code") == "123456":
                return self.json_response({"user": {"id": "1", "username": "admin"}})
            return self.json_response({"error": "Invalid code"}, 401)

        if path == "/api/auth/change-password":
            if body.get("current_password") != "demo123":
                return self.json_response({"error": "Current password is incorrect"}, 401)
            return self.json_response({"message": "Password changed"})

        if path in ["/api/auth/profile", "/api/auth/delete-account", "/api/auth/sessions/revoke", "/api/auth/sessions/revoke-all", "/api/auth/2fa/disable"]:
            return self.json_response({"message": "OK"})

        if path == "/api/auth/onboarding":
            return self.json_response({"message": "Onboarding complete"})

        if path == "/api/support/ticket":
            return self.json_response({"message": "Ticket created", "ticket_id": "TK-ABC123"})

        if path == "/api/team/invite":
            return self.json_response({"message": "Invite sent"})

        if path == "/api/team/invite/cancel":
            return self.json_response({"message": "Invite cancelled"})

        if path == "/api/team/members/remove":
            return self.json_response({"message": "Member removed"})

        if path == "/api/auth/setup-2fa":
            return self.json_response({
                "qr_url": "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/AuthKit:demo?secret=JBSWY3DPEHPK3PXP",
                "manual_key": "JBSWY3DPEHPK3PXP",
                "pending_secret": "mock_secret"
            })

        if path == "/api/auth/confirm-2fa":
            if body.get("code") == "123456":
                return self.json_response({"backup_codes": ["ABCD1234", "EFGH5678", "IJKL9012", "MNOP3456", "QRST7890", "UVWX1234"]})
            return self.json_response({"error": "Invalid code"}, 400)

        if path == "/api/auth/verify-reset-2fa":
            if body.get("code") == "123456" or body.get("backup_code"):
                return self.json_response({"reset_token": "mock_reset_token_2fa"})
            return self.json_response({"error": "Invalid code"}, 401)

        if path == "/api/auth/profile/avatar":
            return self.json_response({"avatar_url": "/avatar.png"})

        if path == "/api/auth/2fa/setup":
            return self.json_response({
                "qr_url": "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/AuthKit:demo?secret=JBSWY3DPEHPK3PXP",
                "manual_key": "JBSWY3DPEHPK3PXP",
                "pending_secret": "mock_secret"
            })

        if path == "/api/auth/2fa/confirm":
            if body.get("code") == "123456":
                return self.json_response({"backup_codes": ["ABCD1234", "EFGH5678", "IJKL9012", "MNOP3456", "QRST7890", "UVWX1234"]})
            return self.json_response({"error": "Invalid code"}, 400)

        if path == "/api/payments/create-intent":
            return self.json_response({"client_secret": "pi_mock_secret_123"})

        if path == "/api/payments/create":
            return self.json_response({"checkout_url": "/payments/success.html?payment_id=mock_123"})

        if path == "/api/payments/apply-coupon":
            if (body.get("coupon_code", "")).upper() == "DEMO50":
                return self.json_response({"valid": True, "discount_amount": 500, "new_total": 499})
            return self.json_response({"valid": False, "error": "Invalid coupon code"})

        if path == "/api/waitlist":
            return self.json_response({"message": "You're on the list!"})

        return self.json_response({"error": "Not found"}, 404)

    def do_PATCH(self):
        path = urlparse(self.path).path
        if path == "/api/auth/profile":
            return self.json_response({"message": "Profile updated"})
        if path == "/api/auth/settings":
            return self.json_response({"message": "Settings updated"})
        return self.json_response({"error": "Not found"}, 404)

    def read_body(self):
        length = int(self.headers.get("Content-Length", 0))
        if length == 0:
            return {}
        try:
            return json.loads(self.rfile.read(length))
        except:
            return {}

    def json_response(self, data, code=200):
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())

    def log_message(self, format, *args):
        if '/api/' in str(args[0]):
            super().log_message(format, *args)


if __name__ == "__main__":
    print(f"\n  Auth Kit running at http://localhost:{PORT}")
    print(f"  Browse templates at http://localhost:{PORT}/index.html")
    print(f"\n  Mock credentials: admin/admin123 or demo/demo123")
    print(f"  Mock 2FA code: 123456")
    print(f"  Mock coupon: DEMO50\n")

    server = HTTPServer(("", PORT), AuthKitHandler)
    server.serve_forever()
