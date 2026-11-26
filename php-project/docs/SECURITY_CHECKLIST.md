# AmarGazipur Security Checklist

- [x] **PDO Prepared Statements**: Every SQL query uses parameterized `PDO::prepare()` with `ATTR_EMULATE_PREPARES => false`.
- [x] **Password Hashing**: Uses `password_hash(..., PASSWORD_DEFAULT)` and `password_verify()`.
- [x] **CSRF Protection**: Constant-time `hash_equals()` verification on all state-changing POST routes.
- [x] **XSS Prevention**: Output escaped via `htmlspecialchars(..., ENT_QUOTES | ENT_HTML5, 'UTF-8')`.
- [x] **Session Security**: `HttpOnly`, `SameSite=Lax`, `session_regenerate_id(true)` on login.
- [x] **File Upload Security**: Real MIME verification via `finfo`, 5MB size cap, random filenames, and `uploads/.htaccess` disabling PHP execution.
- [x] **Brute-Force Protection**: IP-based `login_attempts` rate limiting (6 failures / 15 minutes).
- [x] **RBAC Authorization**: Admin routes check `Auth::requireAdmin()` server-side; Journalist/Landlord/Institution routes check `Auth::hasApprovedCategory()`.
- [x] **Installer Lock**: `install/installed.lock` prevents re-running installation after setup.
- [x] **Directory Protection**: `.htaccess` blocks direct HTTP access to `app/`, `config/`, `database/`, `storage/`, and `.sql`/`.log`/`.md` files.
