# Hotel Room Reservation System

Group project (Software Engineering). Customers search and book rooms and pay; administrators manage rooms, customers, payments and reports.

Team: Angela Wandia, Joyline Taa, Tamara Kaka, Malvin Anyango, Nethan Njenga

## Set up on your computer

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Clone this repo inside `C:\xampp\htdocs`:
   ```bash
   cd /c/xampp/htdocs
   git clone https://github.com/<owner>/hotel-reservation-system.git
   cd hotel-reservation-system
   git checkout dev
   ```
3. Create the database: open http://localhost/phpmyadmin, click **Import**, choose `database/schema.sql`, click **Go**.
   (Importing again wipes every table, so announce it in the group chat before you change `schema.sql`.)
4. Copy `config/db.example.php` to `config/db.php`. The XAMPP defaults already work.
5. Create your own administrator account (run from the project folder in Git Bash):
   ```bash
   /c/xampp/php/php database/create_admin.php "Hotel Admin" admin@hrrs.test "Admin1234"
   ```
6. Open http://localhost/hotel-reservation-system/auth/login.php

## Folder owners

| Folder | Owner |
|---|---|
| `auth/`, `customers/`, `includes/auth.php`, `database/schema.sql` | Angela |
| `rooms/`, `admin/` (rooms, dashboard cards) | Joyline |
| `search/`, `assets/`, `database/seed.sql` | Tamara |
| `reservations/` | Malvin |
| `payments/`, `reports/`, `admin/` (payments, reports) | Nethan |

## Using the shared code

Every page that needs a database or a login starts with:

```php
require_once __DIR__ . '/../includes/auth.php';

$user = requireLogin();             // any logged-in user
$user = requireRole(ROLE_ADMIN);    // administrators only (use ROLE_CUSTOMER for customer-only pages)

$stmt = db()->prepare('SELECT * FROM rooms WHERE room_id = ?');   // always use prepared statements
$stmt->execute([$roomId]);
```

| Helper | What it does |
|---|---|
| `db()` | the one PDO connection |
| `base_url('search/index.php')` | builds a link inside the project, so links work wherever the folder sits |
| `e($text)` | escapes text before printing it in HTML. Always use it on user data. |
| `csrfField()` / `csrfValid()` | put `<?= csrfField() ?>` in every POST form and check `csrfValid()` before saving |
| `flash('success', 'Saved')` | shows a message on the next page |
| `$user['id']`, `['name']`, `['email']`, `['role']` | the logged-in user |

Table names are lower case: `users`, `room_types`, `rooms`, `room_availability`, `reservations`, `payments`.

Work plan and Git workflow: see `WORK_PLAN.md`.
