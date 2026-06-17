# Kai Properties Ltd - Maintenance Ticketing System (MVP)

Laravel-based maintenance platform built from `kai.md` requirements.

## Stack
- Backend: Laravel 13 (PHP 8.4)
- Database: MySQL (configured in `.env.example`)
- Web: Blade templates using HTML5 + CSS3
- Mobile API: REST API for future mobile app integration using Laravel Sanctum tokens

## Features Implemented
- Role-based access (`tenant`, `admin`, `technician`, `approver`)
- Admin-only CRUD for Properties, Categories, and Users in web and API
- Ticket lifecycle statuses:
	- `logged`, `assigned`, `in_progress`, `pending_approval`, `on_hold`, `completed`, `closed`, `rejected`, `overdue`
- Ticket creation, assignment, search/filtering, status updates
- Additional cost request + approval workflow
- Dashboard metrics and technician workload
- Reference APIs for properties, categories, and technicians
- Form Request validation classes for API payload consistency
- API Resource transformers for cleaner mobile response contracts

## Setup
1. Install dependencies:
```bash
composer install
```

2. Copy env file and update database credentials:
```bash
cp .env.example .env
```

3. Generate app key:
```bash
php artisan key:generate
```

4. Create MySQL database:
```sql
CREATE DATABASE kai_maintenance;
```

5. Run migrations and seed demo data:
```bash
php artisan migrate --seed
```

6. Start app:
```bash
php artisan serve
```

Web UI:
- Dashboard: `/`
- Tickets: `/tickets`
- Login: `/login`
- Admin Properties: `/admin/properties`
- Admin Categories: `/admin/categories`
- Admin Users: `/admin/users`

## Demo Login Users (seeded)
All seeded users use password: `password`
- Admin: `admin@kai.local`
- Approver: `approver@kai.local`
- Technician: `tech1@kai.local`, `tech2@kai.local`
- Tenant: `tenant@kai.local`

## Mobile API Quick Start
Base URL:
- `http://127.0.0.1:8000/api/v1`

Authentication:
- Token type: Bearer token from Sanctum

Auth endpoints:
- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`

Reference endpoints:
- `GET /references/properties`
- `GET /references/categories`
- `GET /references/technicians`

Dashboard endpoint:
- `GET /dashboard`

Ticket endpoints:
- `GET /tickets`
- `POST /tickets`
- `GET /tickets/{ticket}`
- `PATCH /tickets/{ticket}/assign`
- `PATCH /tickets/{ticket}/status`
- `POST /tickets/{ticket}/cost-requests`
- `PATCH /cost-requests/{costRequest}/review`

Admin endpoints (admin role only):
- `GET /admin/properties`
- `POST /admin/properties`
- `GET /admin/properties/{property}`
- `PUT/PATCH /admin/properties/{property}`
- `DELETE /admin/properties/{property}`
- `GET /admin/categories`
- `POST /admin/categories`
- `GET /admin/categories/{category}`
- `PUT/PATCH /admin/categories/{category}`
- `DELETE /admin/categories/{category}`
- `GET /admin/users`
- `POST /admin/users`
- `GET /admin/users/{user}`
- `PUT/PATCH /admin/users/{user}`
- `DELETE /admin/users/{user}`

## Mobile Integration Note
This repository is web-first (Laravel + Blade + HTML5/CSS).
Mobile clients can be integrated later by consuming the API endpoints listed above.

### Sample Login Request
```bash
curl --request POST \
	--url http://127.0.0.1:8000/api/v1/auth/login \
	--header 'Content-Type: application/json' \
	--data '{
		"email": "tenant@kai.local",
		"password": "password"
	}'
```

### Sample Create Ticket Request
```bash
curl --request POST \
	--url http://127.0.0.1:8000/api/v1/tickets \
	--header 'Authorization: Bearer YOUR_TOKEN' \
	--header 'Content-Type: application/json' \
	--data '{
		"title": "Broken kitchen tap",
		"description": "Tap is leaking heavily.",
		"property_id": 1,
		"maintenance_category_id": 2,
		"unit": "B-203",
		"priority": "high",
		"estimated_cost": 250.00,
		"etd": "2026-06-20 12:30:00"
	}'
```
