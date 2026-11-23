# Angela's Authentication + Customer Management — Joyline assistance pack

Project: **Hotel Room Reservation System**  
Branch suggested: **feature/auth-login-joyline-assist**  
Stack: PHP 8.2+, MariaDB/MySQL, XAMPP, VS Code.

## IMPORTANT integration notes

- This is a **standalone local demonstration module**, not a verified or fully integrated group release.
- Prepared to help with Angela's **Authentication and Customer Management** assignments; Joyline should commit using **her own GitHub account** and credit Angela as the original module owner/reviewer as agreed with the group.
- Do **not** overwrite Joyline's `rooms/` or the official future `database/schema.sql` when adding these files. The `database/auth_migration.sql` script only adds auth-specific tables into the existing local database.
- The task says Angela owns the complete group schema from the ERD. **No official ERD was included in this pack**, so `auth_migration.sql` is **not** the group's final six-table schema. Angela should confirm keys, naming and relationships with other modules.
- This demo uses the existing `hotel_reservation_system` database that Joyline already created in phpMyAdmin.
- The group must separately merge and test Room Management, Search, Reservation, Payment and Reports on `dev`.

## 1. Check XAMPP

Apache must be running. MariaDB/MySQL must be accessible (even if started from a terminal instead of the XAMPP panel). Visit `http://localhost/phpmyadmin/` to confirm.

## 2. Switch Git branches in VS Code terminal

Make sure your Room Management work is pushed and clean:

```powershell
cd C:\xampp\htdocs\Hotel_Room_Reservation_System
git status
git fetch origin
git switch main
git pull origin main
git switch -c feature/auth-login-joyline-assist
```

If Git says the branch already exists, run `git switch feature/auth-login-joyline-assist` instead. Starting from `main` keeps the work separate from the Room Management PR. It's normal for the `rooms/` files to disappear from your local working tree after you switch to `main`; your room code remains safe on the pushed feature branch.

## 3. Copy the files

Extract this zip. Copy these folders/files into the root of your cloned repository:

- `auth/`
- `includes/`
- `customers/`
- `database/auth_migration.sql` (create `database/` if needed)
- `AUTH_TESTING.md`
- `README_AUTH_ASSIST.md`

Do not copy the pack's outer folder into your repository; copy its contents.

## 4. Import authentication tables (without deleting existing data)

1. Visit `http://localhost/phpmyadmin/`.
2. Click the existing database `hotel_reservation_system` on the left.
3. Click `Import`, choose `database/auth_migration.sql`, then click `Go`.
4. Confirm the new `Customers`, `Auth_Remember_Tokens` and `Auth_Password_Resets` tables appear. Your previous room tables should still be there, with existing room data unaffected.

**Safety:** Never drop the database or import a destructive replacement schema while others' test data is present. Do not push actual database contents, credentials or personal details to GitHub.

## 5. Test customer registration

Open:

`http://localhost/Hotel_Room_Reservation_System/auth/register.php`

Register a **dummy customer** (for example, `demo.customer@example.test`) with a unique 12+ character test password.

Open phpMyAdmin → `hotel_reservation_system` → `Customers` → Browse to confirm a row exists. A real password must never appear in plaintext: only a hash is stored.

## 6. Test login/logout

Open:

`http://localhost/Hotel_Room_Reservation_System/auth/login.php`

Sign in using the customer account. The demo `auth/home.php` shows the user's name and their `Customer` role; click **Sign out** to end the session.

Try an incorrect password and try opening the admin-only page while signed in as the Customer:

`http://localhost/Hotel_Room_Reservation_System/customers/index.php`

Expected: access denied (403) for Customer. Do **not** create an Admin via public registration; all public registrations use role Customer.

## 7. Create a local Admin safely (command-line only)

Open PowerShell terminal in the project root. Set a unique dummy admin email and a strong password using environment variables **in your current terminal session**:

```powershell
$env:HOTEL_ADMIN_EMAIL = "demo.admin@example.test"
$env:HOTEL_ADMIN_NAME = "Demo Administrator"
$securePassword = Read-Host "Choose admin demo password (12+ characters)" -AsSecureString
$env:HOTEL_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new("", $securePassword).Password
& "C:\xampp\php\php.exe" "auth\tools\create_admin.php"
Remove-Item Env:HOTEL_ADMIN_PASSWORD
```

Use a strong **demo-only** password. The password prompt does not display your typing. Never send passwords to teammates in chat and never commit them to GitHub.

Then log in as admin through `auth/login.php` and open:

`http://localhost/Hotel_Room_Reservation_System/customers/index.php`

You should see the customer list and be able to view/edit/deactivate/reactivate dummy customer accounts. Try logging in again as a deactivated customer: the login should be rejected.

## 8. Optional extra features

- **Remember me**: a 14-day cookie is backed by a hashed database token and rotated when used; verify with browser tests. For production, enforce HTTPS.
- **Forgot password**: screens, expiring reset tokens and reset handling are included. **XAMPP generally has no outgoing email configured by default.** A full reset test requires setting up SMTP/email delivery; do not claim this feature works until a reset email is delivered and a password can be changed.

## 9. Test and push

Record actual results in `AUTH_TESTING.md`, including screenshots if your lecturer expects evidence. Check PHP syntax locally:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & "C:\xampp\php\php.exe" -l $_.FullName }
```

Push only from `feature/auth-login-joyline-assist`:

```powershell
git status
git add auth/ includes/auth.php customers/ database/auth_migration.sql AUTH_TESTING.md README_AUTH_ASSIST.md
git commit -m "feat(auth): add customer registration login and admin management"
git push -u origin feature/auth-login-joyline-assist
```

Angela's original planned branches are `feature/auth-login` and `feature/customer-management`; coordinate with Angela about whether this help branch should be reviewed and integrated as-is or split into those two branches before creating PRs.

Make your PR **target `dev`** after the team lead creates it. Do not submit directly to `main`. Review and merge should be handled by the people listed in the assignment document.

## 10. Integrate auth with Joyline's Room Management after the branches are merged

In `rooms/bootstrap.php`, after opening the PHP block and before `protect_room_admin()` executes, add:

```php
require_once __DIR__ . '/../includes/auth.php';
```

The existing room bootstrap uses `function_exists('requireRole')` and will then call `requireRole('Admin')`. The room module will no longer permit anonymous visitors. Re-test both customer-access denial and admin access before presenting this as a merged system.

**Important:** This integration instruction should be applied *after* the group combines both branches on `dev`, not now on the authentication branch. It is a recommended code change, not a modification automatically included in this pack.
