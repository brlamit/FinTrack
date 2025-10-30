# FinTrack

FinTrack is a lightweight personal finance tracker built with Laravel. It helps you record incomes and expenses, categorize transactions, and view simple reports for budgeting and tracking purposes.

## Quick overview

- Project: FinTrack
- Framework: Laravel
- Purpose: Personal finance tracking (expenses, incomes, categories, simple reports)

## Requirements

- PHP 8.0+ (installed)
- Composer
- Node.js & npm (for frontend assets)
- A database supported by Laravel (e.g. MySQL, SQLite, PostgreSQL)

## Installation (development)

Open PowerShell in the project root and run the following steps:

1) Install PHP dependencies:

	composer install

2) Copy and configure environment file:

	copy .env.example .env
	php artisan key:generate

3) Install frontend dependencies and build assets:

	npm install
	npm run dev

4) Run migrations:

	php artisan migrate

5) Start the local development server:

	php artisan serve

This will make the app available at http://127.0.0.1:8000 by default.

Notes for Windows PowerShell: use the `copy` command shown above (or `cp` if you prefer). If you run into permission errors, run the shell as administrator or adjust file permissions accordingly.

## Running tests

You can run PHPUnit tests shipped with the project. From PowerShell:

	vendor\\bin\\phpunit.bat

or (cross-platform):

	./vendor/bin/phpunit

## Development tips

- Environment: set database credentials in `.env` (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
- If you prefer SQLite for quick local testing, create a `database/database.sqlite` file and set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite` in `.env`.

## Contributing

Contributions are welcome. If you want to add features or fix bugs:

1) Fork the repository
2) Create a topic branch: `git checkout -b feat/your-feature`
3) Make changes and add tests where appropriate
4) Open a pull request with a clear description

Be sure to follow the existing code style and add tests for new behavior.

## License

This project is open source and distributed under the MIT License.

---

This project uses the Laravel framework. See the `composer.json` and `LICENSE` files for framework licensing and third-party package details.
