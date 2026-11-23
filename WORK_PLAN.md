# Hotel Room Reservation System: Work Plan and GitHub Workflow

Group members: Angela Wandia, Joyline Taa, Tamara Kaka, Malvin Anyango, Nethan Njenga

This plan splits the build across the five members using the seven modules, six database tables and five wireframes from the Task 3 design document.

---

## 1. Work division

Each person owns one or two modules end to end: backend logic, database queries and the matching screen. Owning whole modules keeps merge conflicts small.

| Member | Modules owned | Tables | Screens (wireframes) | Sequence flows |
|---|---|---|---|---|
| **Angela Wandia** (Git lead) | User Authentication, Customer Management | Users | 7.1 Login and Registration | 6.1 Login, 6.2 Register |
| **Joyline Taa** | Room Management | Room_Types, Rooms, Room_Availability | 7.5 Admin Dashboard (room table, add/edit/remove, summary cards) | 6.5 Admin Adds a Room |
| **Tamara Kaka** | Room Search | (reads Rooms, Room_Types, Room_Availability, Reservations) | 7.2 Room Search | first half of 6.3 (search and availability check) |
| **Malvin Anyango** | Reservation | Reservations | 7.4 My Reservations | 6.3 (create reservation and double-booking re-check), 6.4 Cancel Reservation |
| **Nethan Njenga** | Payment, Reports / Admin | Payments | 7.3 Booking and Payment, report panel of 7.5 | second half of 6.3 (payment and receipt), 6.6 Generate Report |

### Shared responsibilities
| Task | Owner |
|---|---|
| Create repo, branches, protection rules, project board | Angela |
| Database script `database/schema.sql` (all six tables from the ERD) | Angela |
| Seed data `database/seed.sql` (sample rooms, types, users) | Tamara |
| Shared layout: header, navbar, footer, CSS, DB connection file | Tamara |
| Session handling and role check (Customer vs Admin), used by everyone | Angela |
| Final integration testing and demo script | Everyone, led by Angela |
| Final documentation, README, screenshots | Everyone writes their own section; Angela merges |

### Detailed tasks per member

**Angela: Authentication and Customer Management**
- Register: validate fields, check duplicate email, hash password, save with role = Customer
- Login and logout: verify the hash, create a session, redirect by role
- Role-check helper that other modules call to protect admin pages
- Admin Manage Customers page (list, view, edit, deactivate)
- Forgot password and Remember me
- Repo setup, merging pull requests, resolving conflicts

**Joyline: Room Management**
- Add, update and remove rooms (unique room number check)
- Manage Room_Types (name, price per night, capacity, description)
- Manage Room Availability: block date ranges with a reason, such as maintenance
- Admin dashboard layout and the summary cards (total rooms, available today)

**Tamara: Room Search**
- Search form: check-in, check-out, guests, room type
- Query rooms that are not booked or blocked in the period
- Results list with photo, capacity, amenities, price, View Details and Book Now
- Room details page
- Shared layout, CSS and seed data

**Malvin: Reservation**
- `createReservation`: re-check availability at booking time, save as Pending, calculate the total
- My Reservations page with filters by status and date
- Modify reservation (dates, subject to availability)
- Cancel reservation with confirmation, set Cancelled and release the room
- Status changes: Pending, Confirmed, Completed, Cancelled

**Nethan: Payment and Reports**
- Booking and Payment screen with the summary panel
- Record payment (method, amount, status), including partial payments
- Generate receipt number and receipt page
- On payment success, tell the Reservation module to set Confirmed
- Reports: choose type and period, compile totals, display and export
- Admin View Payments page

---

## 2. Integration points (agree these in week 1)

| From | To | Function or contract |
|---|---|---|
| Tamara | Malvin | `isRoomAvailable($roomId, $checkIn, $checkOut)` is used by both search and reservation |
| Malvin | Nethan | `createReservation()` returns a `reservation_id`; Nethan's payment uses it |
| Nethan | Malvin | `confirmReservation($reservationId)` is called after successful payment |
| Angela | Everyone | `requireLogin()` and `requireRole('Admin')` helpers |
| Joyline | Tamara | Room, Room_Types and Room_Availability tables define what search reads |

Write these function signatures in an issue before anyone codes, so everyone builds against the same contract.

---

## 3. Timeline (adjust to your deadline)

| Phase | Work | Who |
|---|---|---|
| **Week 1: Setup** | Everyone installs the tools (section 6). Angela creates the repo, schema and branches. Tamara creates the shared layout. Everyone clones and pushes a test commit. | All |
| **Week 2: Core modules** | Auth, Room Management, Room Search, base Reservation work in parallel | All |
| **Week 3: Connected flows** | Booking to payment to confirmation. Cancel and modify. Reports. Admin dashboard. | Malvin, Nethan, Joyline |
| **Week 4: Finish** | Integration testing, bug fixes, validation, README, demo rehearsal | All |

---

## 4. GitHub workflow

### Repository structure
```
hotel-reservation-system/
├── README.md
├── WORK_PLAN.md
├── database/
│   ├── schema.sql
│   └── seed.sql
├── config/            (db connection)
├── includes/          (header, footer, auth helpers)
├── assets/            (css, js, images)
├── auth/              (Angela)
├── customers/         (Angela)
├── rooms/             (Joyline)
├── admin/             (Joyline, Nethan)
├── search/            (Tamara)
├── reservations/      (Malvin)
├── payments/          (Nethan)
├── reports/           (Nethan)
└── docs/              (design document, screenshots)
```
Each person works mainly in their own folders, which avoids most conflicts. Shared files (`includes/`, `assets/`, `database/`) go through a pull request and a review from the file's owner.

### Branches
- `main`: always working, never pushed to directly
- `dev`: where finished features are merged and tested together
- Feature branches, created from `dev`:
  - `feature/auth-login` (Angela)
  - `feature/room-management` (Joyline)
  - `feature/room-search` (Tamara)
  - `feature/reservations` (Malvin)
  - `feature/payments` (Nethan)

### Daily routine for every member
```bash
git checkout dev
git pull origin dev
git checkout -b feature/your-feature
# ... work, then:
git add .
git commit -m "feat(search): add availability query"
git push origin feature/your-feature
```
Then open a pull request into `dev` on GitHub.

### Commit message format
`type(module): short description`
Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`
Example: `fix(reservation): block overlapping dates`

### Pull request rules
1. Every PR targets `dev`, never `main`.
2. At least one teammate reviews it. Angela merges.
3. Pull the latest `dev` into your branch before opening the PR.
4. Describe what changed and how to test it.
5. Merge `dev` into `main` only when the whole group agrees it is stable.

### Issues and project board
- Create one GitHub Issue per task in section 1 and assign it to its owner.
- Labels: `auth`, `rooms`, `search`, `reservation`, `payment`, `reports`, `bug`, `docs`
- Use a GitHub Project board with columns To do, In progress, In review, Done.

### Angela's one-time setup
1. Create the repo (private) and add the other four as collaborators.
2. Create `dev`. Turn on branch protection for `main` and `dev` (require pull request).
3. Add a `.gitignore` (ignore `config/db.php` passwords and `.env`) and a README.
4. Push `schema.sql` so everyone builds the same database.

---

## 5. Rules to avoid trouble
- Do not commit database passwords. Keep a `config/db.example.php` in the repo.
- Never edit another person's module without telling them. Open an issue instead.
- Pull from `dev` at least once a day.
- If `schema.sql` changes, announce it in the group chat so everyone re-imports it in phpMyAdmin.
- Commit small and often, rather than one huge commit before the deadline.

---

## 6. Environment setup (adapted from the other group's guide)

Everyone installs these, with the same versions:

| # | Tool | Notes |
|---|---|---|
| 1 | XAMPP (PHP 8.2+) | Tick only Apache, MySQL, PHP, phpMyAdmin. Install to `C:\xampp`. |
| 2 | Git for Windows | Click Next on every screen, pick VS Code as the default editor. |
| 3 | VS Code | Tick Add to PATH and Open with Code. |
| 4 | Composer | Use `C:\xampp\php\php.exe` when asked for PHP. Only needed if the group uses PHP libraries. |
| 5 | GitHub account | Post your username in the group so Angela can add you. |
| 6 | VS Code extensions | PHP Intelephense (Ben Mewburn), Prettier, GitLens |
| 7 | Postman (optional) | Useful for Nethan and Malvin to test payment and reservation requests. |

The other group's ngrok and per-person Postman assignments do not apply here. You only need ngrok if you want to demo the system to someone outside your network.

Check that it works:

| Check | How | Expected |
|---|---|---|
| Apache and MySQL | XAMPP Control Panel, Start both | Both turn green |
| Web server | `http://localhost` | XAMPP page opens |
| Database | `http://localhost/phpmyadmin` | phpMyAdmin opens |
| Git | Git Bash: `git --version` | Version number |
| PHP | Git Bash: `/c/xampp/php/php -v` | PHP 8.2 or higher |

If Apache will not start, close Skype or any other app using port 80, then press Start again.

Put the project folder inside `C:\xampp\htdocs\` (clone the repo there) so it runs at `http://localhost/hotel-reservation-system`.

---

## 7. Definition of done (per task)
- Works on the local XAMPP setup with the shared `schema.sql`
- Validates input and handles errors (for example duplicate email, unavailable room)
- Matches the wireframe and the sequence diagram for that flow
- Reviewed by a teammate and merged into `dev`
- Issue closed and a short note added to your section of the README
