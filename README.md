# PriestAgenda

PriestAgenda is a Laravel 10 / Blade application for Orthodox priests to publish confession appointment slots and manage requests. Christenings and marriages are normally arranged for Saturdays or Sundays; couples use a confession request and note the ceremony and preferred date to discuss arrangements, including requests for another day.

## Local setup

1. Configure `.env` with your database and mail settings.
2. Run `php artisan migrate` (also updates the existing Laravel users table with priest profile and phone fields).
3. Start the app with `php artisan serve` (or configure the Laragon virtual host).
4. Sign in using an email address. A visitor account is created automatically for new contacts. The local `.env` sends email through Mailpit at `127.0.0.1:1025`.

Email codes use Laravel's configured mail driver. For local development, Mailpit can capture them. SMS codes require a Twilio account; set `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, and `TWILIO_FROM_NUMBER` in `.env`.

## Seeded accounts

Run `php artisan db:seed --force` to create the local administrator and demo priest. The administrator signs in passwordlessly at `admin@priestagenda.test`; its six-digit OTP arrives in Mailpit at `http://127.0.0.1:8025`. The demo priest profile is `father-alexios-demo` and is clearly labelled as sample data. Replace or remove it before using the site for a real parish.

## Enable a priest account

New sign-ins receive the visitor role. Priest accounts must be explicitly provisioned by the site administrator so a visitor cannot grant themselves priest privileges. The administrator can create accounts from Dashboard → Create a priest account. For manual provisioning, promote the priest's verified email from Tinker:

```php
php artisan tinker
App\Models\User::where('email', 'priest@example.com')->update(['role' => 'priest']);
```

The priest can then open Dashboard → Edit profile & availability, add a profile and publish weekly hours or blocked dates. Each profile has its own public page and calendar. Blocked-date reasons are private to the priest.

## V1 behavior

- Public profiles show the priest's parish, city, bio, and recurring service hours.
- Visitors can browse and select confession slots without signing in. Clicking “Sign in to request this time” starts OTP sign-in; after verification they return to the selected slot and submit the request. Visitors land on their dashboard, while priests and administrators land on their role-specific dashboard.
- Priests can block individual dates or date ranges for fasting periods and other restrictions. The same weekly hours can be applied to multiple weekdays at once; christening and marriage hours are restricted to Saturdays and Sundays.
- Priest and requester can exchange private messages on each appointment request. Messages appear in the dashboard and notify the other participant by in-app notification and configured email/SMS channels.
- Calendar availability is calculated from weekly service hours, blocked dates, and pending or confirmed appointments.
- Requests are pending until the priest confirms or declines them. A decline includes an alternative date and time.
- Appointment requests and responses are saved in each account's in-app notification inbox; email notifications use the configured mailer, and SMS is sent when Twilio is configured.
- OTP codes expire after ten minutes. Email and SMS sign-in use an email address or E.164 phone number.
- Contact details and private calendar blocks are not exposed on public pages.

## JSON API

All API routes use the `/api/v1` prefix and return JSON. Authenticated routes use Sanctum bearer tokens.

| Method | Route | Purpose |
| --- | --- | --- |
| POST | `/auth/code` | Send an email or SMS sign-in code (`destination`, optional `name`) |
| POST | `/auth/verify` | Verify (`destination`, `code`); returns a bearer token |
| GET | `/me` | Current account |
| DELETE | `/auth/token` | Revoke current token |
| GET | `/notifications` | List notifications and unread count |
| POST | `/notifications/{id}/read` | Mark one notification as read |
| POST | `/notifications/read-all` | Mark all notifications as read |
| POST | `/admin/priests` | Admin only: create a priest account |
| GET | `/priests` | List public priest profiles |
| GET | `/priests/{slug}` | Public priest profile and weekly hours |
| GET | `/priests/{slug}/availability?date=YYYY-MM-DD&service_type=confession` | Open time slots |
| POST | `/priests/{slug}/appointments` | Request a slot (signed-in visitor) |
| GET | `/appointments` | Current visitor's requests |
| GET / POST | `/appointments/{id}/messages` | Read or send private messages for a request (participants only) |
| GET | `/priest/appointments` | Priest's incoming requests |
| GET | `/priest/calendar?month=YYYY-MM` | Priest's private monthly event calendar |
| PATCH | `/priest/appointments/{id}/response` | Confirm or decline with a suggested time |
| PUT | `/priest/profile` | Edit current priest's public profile |
| POST / DELETE | `/priest/availability[/{id}]` | Manage recurring hours (`weekdays: [0,6]` applies a time window every Sunday and Saturday) |
| POST / DELETE | `/priest/blocked-dates[/{id}]` | Manage private blocked dates |
