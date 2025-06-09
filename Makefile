.PHONY: help install-sail setup start stop restart test test-coverage bash migrate seed queue schedule queue-failed

# Default target
help:
	@echo "Available commands:"
	@echo "  make install-sail   - Install Laravel Sail using Docker (no local PHP required)"
	@echo "  make setup          - Install dependencies and set up the project"
	@echo "  make start          - Start the Docker containers"
	@echo "  make stop           - Stop the Docker containers"
	@echo "  make restart        - Restart the Docker containers"
	@echo "  make queue          - Start the queue worker for processing jobs"
	@echo "  make schedule       - Run the scheduler for periodic tasks"
	@echo "  make queue-failed   - View failed jobs"
	@echo "  make test           - Run tests"
	@echo "  make test-coverage  - Run tests with coverage report"
	@echo "  make bash           - Open a bash shell in the app container"
	@echo "  make migrate        - Run database migrations"
	@echo "  make seed           - Run database seeders"

# Install Laravel Sail using Docker (no local PHP required)
install-sail:
	docker run --rm \
		-u "$$(id -u):$$(id -g)" \
		-v "$$(pwd):/var/www/html" \
		-w /var/www/html \
		laravelsail/php82-composer:latest \
		composer install --ignore-platform-reqs

# Setup the project
setup: install-sail
	cp -n .env.example .env || true
	./vendor/bin/sail up -d
	./vendor/bin/sail artisan key:generate
	./vendor/bin/sail artisan migrate
	./vendor/bin/sail npm install
	./vendor/bin/sail npm run dev

# Start the Docker containers
start:
	./vendor/bin/sail up -d

# Stop the Docker containers
stop:
	./vendor/bin/sail down

# Restart the Docker containers
restart:
	./vendor/bin/sail down
	./vendor/bin/sail up -d

# Start the queue worker
queue:
	./vendor/bin/sail artisan queue:work --queue=price-checks,default

# View failed jobs
queue-failed:
	./vendor/bin/sail artisan queue:failed

# Run the scheduler
schedule:
	./vendor/bin/sail artisan schedule:work

# Run tests
test:
	./vendor/bin/sail artisan test

# Run tests with coverage
test-coverage:
	./vendor/bin/sail artisan test --coverage

# Open a bash shell in the app container
bash:
	./vendor/bin/sail bash

# Run database migrations
migrate:
	./vendor/bin/sail artisan migrate

# Run database seeders
seed:
	./vendor/bin/sail artisan db:seed