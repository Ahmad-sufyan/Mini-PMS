# Mini PMS

[![tests](https://github.com/Ahmad-sufyan/Mini-PMS/actions/workflows/tests.yml/badge.svg)](https://github.com/Ahmad-sufyan/Mini-PMS/actions/workflows/tests.yml)

A patient management system for a small clinic, built with Laravel and React. Reception registers patients, books appointments, checks them in and takes payments. Doctors see their day, record consultations and print prescriptions. Administrators manage staff and get an overview of the whole clinic.

It's a portfolio project. Every patient, appointment and invoice in it is fake.

## Features

### By role

| Role              | What they can do                                                                                                                                                                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Receptionist**  | Today's arrivals with one-click check-in, plus quick "New patient" and "New appointment" actions. Register and edit patients, book and reschedule appointments, create invoices and record payments.                                                                           |
| **Doctor**        | Today's schedule with the next patient highlighted and a "Start visit" button. Record consultations (vitals, complaint, diagnosis, notes, follow-up date) with prescriptions, and read each patient's history. Doctors only see their own appointments.                        |
| **Administrator** | A clinic dashboard with total patients, today's appointments, revenue this month and the outstanding balance, plus charts of appointments per day, revenue per month and appointments by status. Manage staff accounts and roles, void invoices, and restore deleted patients. |

### Across the app

- **Patients:** every patient gets a medical record number (MRN). Search by name, MRN or phone, filter and sort the list, and see allergies flagged wherever the patient appears. Deleted records can be restored.
- **Global search:** press <kbd>Ctrl</kbd> <kbd>K</kbd> (or <kbd>⌘</kbd> <kbd>K</kbd>) anywhere to jump to a patient by name, MRN or phone.
- **Appointments:** a day or date-range list filtered by doctor and status, and a week calendar that can show one doctor or all of them. Bookings are checked against opening hours and the doctor's other appointments. Each appointment moves through scheduled, checked in, completed, cancelled or no-show.
- **Visits and prescriptions:** a consultation record for each completed appointment, with a printable A4 prescription.
- **Billing:** invoices built from a price list, with discounts, part payments by cash, card or bank transfer, voiding, and a printable A4 invoice.
- **Permissions:** each role is a set of permissions checked by Laravel policies on the server. The interface only shows what you're allowed to use.
- **Accounts:** sign in with a password, two-factor authentication or a passkey. Light, dark and system themes.
- **Mobile friendly:** the sidebar collapses, and tables hide secondary columns on small screens.

## Tech stack

- **Backend:** [Laravel 13](https://laravel.com) on PHP 8.3+, with Fortify for authentication, Form Requests for validation and policies for authorization
- **Frontend:** [React 19](https://react.dev) and TypeScript, using [Inertia.js 3](https://inertiajs.com), with no separate API
- **UI:** [Tailwind CSS 4](https://tailwindcss.com), [shadcn/ui](https://ui.shadcn.com) components, and shadcn charts built on Recharts
- **Routing:** [Wayfinder](https://github.com/laravel/wayfinder) generates typed route and form helpers from the Laravel routes
- **Database:** SQLite by default (any database Laravel supports will work)
- **Quality:** [Pest](https://pestphp.com) feature tests, PHPStan (Larastan) at level 7, Pint, and Vite+ for linting and formatting, all run in GitHub Actions

The project started from the official [Laravel React starter kit](https://github.com/laravel/react-starter-kit).

## Demo logins

After seeding, every account uses the password `password`.

| Role          | Email                 | Name                             |
| ------------- | --------------------- | -------------------------------- |
| Administrator | `admin@demo.test`     | Clinic Admin                     |
| Receptionist  | `reception@demo.test` | Rita Reception                   |
| Doctor        | `doctor@demo.test`    | Dr. Amina Khan, General Practice |
| Doctor        | `doctor2@demo.test`   | Dr. Daniel Reyes, Pediatrics     |
| Doctor        | `doctor3@demo.test`   | Dr. Mei Chen, Dermatology        |

The seeder builds a whole demo clinic: 60 patients, two months of appointment history and two weeks ahead, a consultation and prescription for every completed visit, and invoices in every state from paid to void. Appointment statuses follow the time you seed. Seed during opening hours (09:00–17:00, Monday to Saturday, by default) and some patients will be waiting while others are still to come.

## Local setup

You'll need PHP 8.3+, Composer and Node.js 22+.

```bash
git clone https://github.com/Ahmad-sufyan/Mini-PMS.git
cd Mini-PMS
composer setup
php artisan migrate:fresh --seed
composer run dev
```

`composer setup` installs the PHP and Node dependencies, creates `.env` from `.env.example`, generates the app key, creates the SQLite database and builds the frontend. `composer run dev` starts the Laravel server and Vite together. Open [http://localhost:8000](http://localhost:8000) and sign in with one of the demo logins.

### Configuration

The clinic's details live in `.env`:

| Variable                              | Default                        | Purpose                                            |
| ------------------------------------- | ------------------------------ | -------------------------------------------------- |
| `CLINIC_NAME`                         | Mini PMS Clinic                | Shown on the dashboard, invoices and prescriptions |
| `CLINIC_ADDRESS`, `CLINIC_PHONE`      | 123 Health Street, Springfield | Printed on invoices and prescriptions              |
| `CLINIC_TIMEZONE`                     | UTC                            | The clinic's local time                            |
| `CLINIC_OPENS_AT`, `CLINIC_CLOSES_AT` | 09:00, 17:00                   | Opening hours for bookings                         |
| `CLINIC_WORKING_DAYS`                 | mon,tue,wed,thu,fri,sat        | Days the clinic is open                            |
| `CLINIC_CURRENCY`                     | USD                            | Currency for prices, invoices and payments         |

### Tests and checks

```bash
composer test
```

```bash
composer ci:check
```

`composer ci:check` runs the same checks as CI: linting and formatting, TypeScript and PHPStan type checks, and the Pest test suite. `composer lint` fixes PHP code style.

## Screenshots

Screenshots go in `docs/screenshots/`.

| Admin dashboard                                          | Doctor dashboard                                           |
| -------------------------------------------------------- | ---------------------------------------------------------- |
| ![Admin dashboard](docs/screenshots/admin-dashboard.png) | ![Doctor dashboard](docs/screenshots/doctor-dashboard.png) |

| Reception dashboard                                              | Patient search                                         |
| ---------------------------------------------------------------- | ------------------------------------------------------ |
| ![Reception dashboard](docs/screenshots/reception-dashboard.png) | ![Patient search](docs/screenshots/patient-search.png) |

| Patient record                                         | Week calendar                                        |
| ------------------------------------------------------ | ---------------------------------------------------- |
| ![Patient record](docs/screenshots/patient-record.png) | ![Week calendar](docs/screenshots/week-calendar.png) |

| Printable invoice                                  | Dark mode                                    |
| -------------------------------------------------- | -------------------------------------------- |
| ![Printable invoice](docs/screenshots/invoice.png) | ![Dark mode](docs/screenshots/dark-mode.png) |
