# Authentication and Customer Management — test checklist

**Project:** Hotel Room Reservation System  
**Code prepared for:** Joyline assisting Angela  
**Status:** Suggested tests only — run on your own XAMPP before writing PASS.

| ID | What is being tested | How to test | Expected result | Actual result | Pass/Fail |
|---|---|---|---|---|---|
| A1 | Customer registration | Register a new customer with a unique email and a 12+ character password, then inspect `Customers` in phpMyAdmin | Customer record is created; role is Customer, password stored as a hash (not plaintext) | To be recorded | Not tested |
| A2 | Duplicate email validation | Try registering again using the exact same email | Error says the email is already registered; no second record is added | To be recorded | Not tested |
| A3 | Login/logout | Sign in with correct password, then sign out using the button | Dashboard opens after login; login page after logout | To be recorded | Not tested |
| A4 | Incorrect password | Attempt login with wrong password | Login rejected; account not opened | To be recorded | Not tested |
| A5 | Admin-only access | While signed in as a Customer, visit `/customers/index.php` | Access denied (403); cannot see customer list | To be recorded | Not tested |
| A6 | Customer management | Sign in as admin, edit customer name, deactivate account, attempt customer login | Updated name appears; inactive account cannot log in | To be recorded | Not tested |
| A7 | Remember me | Login with checkbox selected, close and reopen browser to access `/auth/home.php` | Session re-established via valid cookie for up to 14 days | To be recorded | Not tested |
| A8 | Password reset | Configure SMTP, request a link for a registered user, open the link and reset password | Reset link arrives; token expires after 30 minutes and cannot be reused | To be recorded | Not tested / SMTP setup required |

Use screenshots from the browser and (where appropriate) phpMyAdmin as evidence. Do not share passwords or reset-token links in screenshots. Test with dummy email addresses, not real personal accounts.

## Three recommended presentation demonstrations

1. Register a new Customer and show their record in the `Customers` table. Explain: **input form → validation → hashed password → DB row**.
2. Re-register using the same email to demonstrate the duplicate validation error. Explain: **unique email constraint**.
3. Login with Customer credentials and visit admin Customer Management URL. Explain: **authorization prevents Customer accounts from accessing administrator pages**.

Then show an Admin login and edit/deactivate a sample customer, if time allows.
