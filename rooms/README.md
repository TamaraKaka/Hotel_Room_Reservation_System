# Joyline Taa — Room Management module

**Branch:** `feature/room-management`  
**Owned folders:** `rooms/`, `admin/` (room dashboard only)  
**Stack:** PHP 8+, MySQL/MariaDB, XAMPP.  
**Integration state:** Independently runnable local demo. **Not yet integrated** with the group's authentication, shared database schema or reservations/search modules.

## Set up locally on Windows

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Clone the group repository into `C:\xampp\htdocs\Hotel_Room_Reservation_System`.
3. Create/check out your `feature/room-management` branch. Copy the `rooms` and `admin` folders from the downloadable pack into the repository root.
4. Open `http://localhost/phpmyadmin/`. Click **Import**, choose `rooms/demo_schema.sql`, then click **Import**. It creates the `hotel_reservation_system` database and four local demonstration tables. Alternatively, paste its contents into phpMyAdmin's SQL tab.
5. Visit `http://localhost/Hotel_Room_Reservation_System/rooms/index.php`.
6. Go to **Room Types**, create `Standard` / `KES 6500` / `2 guests`; then go to **Rooms** and create room `101`.
7. Visit `http://localhost/Hotel_Room_Reservation_System/admin/dashboard.php` to see live summary counts.

The local database connection defaults to XAMPP: MySQL host `127.0.0.1`, database `hotel_reservation_system`, user `root`, password blank. For a different configuration set `HOTEL_DB_HOST`, `HOTEL_DB_NAME`, `HOTEL_DB_USER`, `HOTEL_DB_PASS` environment variables before running Apache. **Do not commit passwords.**

## Features

- Add/edit Room Types with name, capacity, description and nightly KES price.
- Create Rooms with unique room numbers. A new room automatically gets `Available` status.
- Update room details and statuses (`Available`, `Maintenance`, `Out of Service`).
- Remove rooms **only if no active future/current Pending or Confirmed reservation exists**. Removal is soft (archived) to preserve history.
- Block room date ranges for maintenance; reject reversed dates, overlapping maintenance blocks and overlapping active reservations. Remove maintenance blocks.
- Admin dashboard: total active rooms, available rooms today, room types and currently blocked rooms. Available today excludes rooms not marked Available, reserved today, or blocked today.
- Use prepared SQL statements for writes, CSRF tokens for POST forms, escaped page output, and server-side validation.

## Important team integration note

This module uses **provisional table columns** until Angela's official six-table `database/schema.sql` is shared. Tell Angela, Tamara and Malvin the proposed schema and agree the final columns before integrating. Angela owns the official schema and admin login; Tamara owns the shared layout/search and database connection; Malvin owns the Reservations table. Do not silently overwrite their files. Specifically, agree whether their primary keys use `id` or another name, and align it consistently across modules.

**Demo security restriction:** When `requireRole('Admin')` is not connected, the pages only allow requests from the local machine (`127.0.0.1` or `::1`). This is not an authentication implementation. Before deploying, import the team's `includes/auth.php` and call their `requireRole('Admin')` so anonymous users cannot manage rooms. Keep the demo unavailable publicly.

## Automated validation checks (database not required)

From the repository root, open a terminal and run:

```bash
php rooms/tests/test_validation.php
```

This verifies room-number, price, capacity and maintenance-date validation. These checks alone **do not prove** the database/UI operations work. Use the manual integration test checklist in `rooms/TESTING_CHECKLIST.md` and record actual outcomes.

## Suggested pull requests

1. **PR 1:** room types and add room.
2. **PR 2:** edit/remove room and room list table.
3. **PR 3:** maintenance availability and dashboard summary cards.

Because everything is provided as one development pack, create **three commits** corresponding to these sections if feasible and open PRs in coordination with Tamara (reviewer) and Angela (merger to `dev`). Never claim a PR exists until actually created on GitHub.

## Proposed function/data agreement for teammates

- `Room_Types(id, type_name, price_per_night, capacity, description)`
- `Rooms(id, room_number UNIQUE, room_type_id, floor, status, description, is_active)`
- `Room_Availability(id, room_id, start_date, end_date, reason)` with inclusive blocked dates
- `Reservations(id, room_id, check_in, check_out, status)` is **temporary testing only**; final structure belongs to Malvin and Angela.
- Search and reservation can treat a room as available only if its `is_active=1`, `status='Available'`, it has no maintenance block overlapping the requested stay, and it has no overlapping `Pending`/`Confirmed` reservation. The team should finalize the `isRoomAvailable($roomId, $checkIn, $checkOut)` contract in a GitHub Issue.

## Presentation explanation

> I developed the Room Management module. An administrator can create room types, add rooms with unique room numbers, update room information, remove rooms without active reservations and block maintenance dates. The dashboard displays live inventory totals. My code is on the `feature/room-management` branch, and the next task is integration with the team's shared login, official database schema and other modules.
