# Local Development Setup

Use a fresh local checkout and a separate empty PostgreSQL database. These instructions are for development, not the production server.

The automated workflows use PHP 8.4, PostgreSQL 18 and Node.js 22. Install Composer and npm, and enable PHP's PostgreSQL driver (`pdo_pgsql`) and the extensions required by `composer.lock`.

1. Clone the repository and enter its folder:

   ```bash
   git clone https://github.com/jlcuenca00/DAR-LTCMS.git
   cd DAR-LTCMS
   composer install
   ```

2. Composer normally creates `.env` from `.env.example` on a new checkout. If it does not exist, copy the example with `cp .env.example .env` (PowerShell: `Copy-Item .env.example .env`). Keep an existing `.env` rather than overwriting it.

3. Create a dedicated empty database, for example `dar_ltcms_local`, through pgAdmin or PostgreSQL tools. In `.env`, keep `APP_ENV=local`, set `DB_DATABASE=dar_ltcms_local`, and enter your local database host, port, username and password. The example's legacy default is `dar_iland`; verify the actual target before running migrations or test seeders. Never point this checkout at production.

4. Prepare the application:

   ```bash
   php artisan key:generate
   php artisan migrate
   npm ci
   npm run build
   ```

5. For an empty, disposable tester database, follow the [tester handoff](barebones-tester-handoff.md). Its seeder clears records even when called on its own; never run it against a database you need to keep. The testing-only initial username is `staff.tester` after that setup. Public registration is not a way to create Staff accounts.

6. Start the local website:

   ```bash
   php artisan serve
   ```

   Open [127.0.0.1:8000](http://127.0.0.1:8000). For frontend development, run `npm run dev` in another terminal.

The local example sends mail to application logs, not an inbox. Use a test mail service if you need to check email delivery. Current application notifications are synchronous; a queue worker is not required for the implemented flows.

Create a separate empty `dar_iland_beta_testing` PostgreSQL database for the default `phpunit.xml` configuration before running `php artisan test`. Verify the effective test connection and credentials first: database tests may reset records in that target. Never run them against production.

