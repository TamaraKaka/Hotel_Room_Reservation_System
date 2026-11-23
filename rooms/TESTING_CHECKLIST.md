# Room Management — Testing log (complete using actual outcomes)

**Tester:** Joyline Taa  
**Environment:** Windows / XAMPP / Chrome or Edge / local MySQL  
**Date of test:** ______________

These are **test plans**, not a claim that browser/database integration tests have passed. Enter actual results after running them.

| ID | What is tested | How to test | Expected result | Actual result | Pass / Fail |
| --- | --- | --- | --- | --- | --- |
| RM-01 | Add room | Add Standard room `101`, floor `1` | Room appears in room list, status is `Available` | __________ | __________ |
| RM-02 | Duplicate room validation | Attempt to add a second room `101` | Error: `Room number already exists`; only one room `101` | __________ | __________ |
| RM-03 | Edit room | Edit room `101` status to `Maintenance` | Updated status is shown in room list; Available Today decreases if appropriate | __________ | __________ |
| RM-04 | Maintenance date block | Change room `101` status back to `Available`; block today's date | New block appears; Available Today excludes the room | __________ | __________ |
| RM-05 | Reject inverted dates | Create block with an end date before its start date (if browser blocks entry, use a form submission test or show client validation) | Invalid date range is rejected | __________ | __________ |
| RM-06 | Prevent deletion of reserved room | Create active test reservation in DB as described below; click Remove room `101` | Error: cannot remove room with an active reservation | __________ | __________ |
| RM-07 | Dashboard | Check summary before and after adding a room | Total Rooms increases by 1; cards refresh from DB | __________ | __________ |

## SQL for Test RM-06 (run ONLY in your local demo database)

After adding room `101`, open **phpMyAdmin → hotel_reservation_system → SQL** and execute:

```sql
INSERT INTO Reservations (room_id, check_in, check_out, status)
SELECT id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Confirmed'
FROM Rooms WHERE room_number='101' AND is_active=1;
```

Back in the Room inventory, try **Remove** room `101` and capture the error. After the test, clean up the *test record only*:

```sql
DELETE FROM Reservations WHERE room_id = (
  SELECT id FROM Rooms WHERE room_number='101'
) AND status='Confirmed';
```

**Warning:** This cleanup query assumes your local demo database contains only this sample confirmed reservation. Do not run it in a shared database; it could delete real records. Prefer identifying the test reservation ID and deleting only that row. 

## Automated checks (database-free)

```bash
php rooms/tests/test_validation.php
```

Copy the terminal output to a screenshot for supporting evidence. It tests room-number syntax, positive room prices, capacity limits and date-range validation, but does not replace the manual tests above.

## Evidence to attach to your presentation

- Screenshot: successful room type + room entry
- Screenshot: duplicate room number error
- Screenshot: maintenance block / Available Today dashboard
- Screenshot: active reservation deletion prevented (if tested)
- Screenshot: terminal test output and GitHub commit/PR history

## 3-test presentation table template

| Test case | Test steps | Actual outcome | Status |
| --- | --- | --- | --- |
| Add room | Enter Standard / 101 / floor 1, Save | [Enter actual outcome] | [Pass/Fail] |
| Prevent duplicate room | Enter 101 again, Save | [Enter actual outcome] | [Pass/Fail] |
| Prevent deleting reserved room | Create Confirmed reservation for 101, attempt Remove | [Enter actual outcome] | [Pass/Fail] |
