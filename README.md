# FitnessHub - FitnessHub System

fintess-finalcode is a fitness tracking and management application.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admins`, `appointments`, `attendance`, `diet_plans`, `fitness_progress`, `members`, `membership_plans`, `member_memberships`, `payments`, `trainers`, `trainer_availability`, `trainer_reviews`, and related records.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/add_member.php`](admin/add_member.php), [`admin/add_membership_plan.php`](admin/add_membership_plan.php), [`admin/add_trainer.php`](admin/add_trainer.php), [`admin/add_trainer_availability.php`](admin/add_trainer_availability.php), [`admin/admin_home.php`](admin/admin_home.php) |
| Root Pages | [`login.php`](login.php) |
| Member | [`member/make_appointment.php`](member/make_appointment.php), [`member/member_dashboard.php`](member/member_dashboard.php), [`member/member_plans.php`](member/member_plans.php), [`member/payment.php`](member/payment.php), [`member/purchase_plan.php`](member/purchase_plan.php) |
| Trainer | [`trainer/attendance.php`](trainer/attendance.php), [`trainer/diet_plans.php`](trainer/diet_plans.php), [`trainer/fitness_progress.php`](trainer/fitness_progress.php), [`trainer/trainer_dashboard.php`](trainer/trainer_dashboard.php), [`trainer/view_bookings.php`](trainer/view_bookings.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/fintess-finalcode.git
cd fintess-finalcode
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/fitness_db.sql`](Database/fitness_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`config.php`](config.php), [`member/config.php`](member/config.php), [`trainer/config.php`](trainer/config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
