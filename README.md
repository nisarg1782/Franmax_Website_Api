# Franmax_Website_Api

## Run locally with Docker

Prereqs: Docker Desktop or Docker Engine + Docker Compose plugin.

1) Copy env file

	cp .env.example .env

	You can keep the defaults for local use. The app container reads these variables.

2) Start the stack (build image and run containers)

	docker compose up --build -d

	- App: http://localhost:8080
	- phpMyAdmin: http://localhost:8081 (server: db, user: root, pass: root)

3) Install PHP deps inside the running app container (required on first run)

	Because the project folder is bind-mounted into the container, you need to install dependencies into the mounted `vendor/` directory. Run:

	docker compose exec app composer install --no-interaction --prefer-dist --no-progress

4) Logs

	docker compose logs -f app

5) Stop

	docker compose down

Notes
- Database data persists in the `db_data` Docker volume.
- The `uploads/` folder is mounted for easy file access during development.
- Credentials in `db.php` are read from environment variables and `.env` if present via `vlucas/phpdotenv`.

