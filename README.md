# OLX Price Tracker

A Laravel application that tracks price changes on OLX advertisements and notifies subscribers when prices change.

## Features

- Subscribe to OLX advertisements to track price changes
- Email verification for subscriptions with 24-hour token expiration
- Automatic price checking with fallback parsing methods
- Email notifications when prices change
- Support for multiple OLX domains (olx.pl, olx.ua, olx.ro, olx.bg, olx.pt)
- Robust error handling and logging
- API documentation with Swagger/OpenAPI

## Requirements

- Docker & Docker Compose

That's it! No local PHP or Composer installation required.

## Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd test_olx
```

2. Install Laravel Sail using Docker (no local PHP required):
```bash
make install-sail
```

3. Set up the project:
```bash
make setup
```

4. Start the queue worker:
```bash
make queue
```

Alternatively, you can run the setup steps manually:
```bash
# Install Laravel Sail using Docker
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs

# Set up the project
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Start the queue worker
./vendor/bin/sail artisan queue:work --queue=price-checks,default
```

5. The application should now be running at http://localhost

## API Documentation

The API documentation is available at:
```
http://localhost/api/documentation
```

This interactive documentation allows you to:
- Explore available API endpoints
- See request and response formats
- Test API calls directly from the browser

## Available Make Commands

- `make install-sail` - Install Laravel Sail using Docker (no local PHP required)
- `make setup` - Install dependencies and set up the project
- `make start` - Start the Docker containers
- `make stop` - Stop the Docker containers
- `make restart` - Restart the Docker containers
- `make queue` - Start the queue worker for processing jobs
- `make schedule` - Run the scheduler for periodic tasks
- `make test` - Run tests
- `make test-coverage` - Run tests with coverage report
- `make bash` - Open a bash shell in the app container
- `make migrate` - Run database migrations
- `make seed` - Run database seeders

## Testing

Run the tests with:
```bash
make test
```

Or manually:
```bash
./vendor/bin/sail artisan test
```

## How It Works

1. Users subscribe to an OLX advertisement by providing the URL and their email
2. Users verify their email address by clicking the link in the verification email (valid for 24 hours)
3. The system periodically checks the prices of all advertisements using multiple parsing strategies
4. When a price changes, the system notifies all verified subscribers and keeps a history of price changes

## Improvements

- Added token expiration for email verification (24 hours)
- Improved price parsing with fallback methods for different HTML structures
- Added support for multiple OLX domains and URL patterns
- Implemented exponential backoff for rate limiting
- Added queue workers for background processing
- Added API documentation with Swagger/OpenAPI

## Configuration

The application can be configured through environment variables or the `config/olx.php` file:

- `OLX_FETCH_ATTEMPTS` - Number of attempts to fetch a price (default: 3)
- `OLX_FETCH_TIMEOUT` - Timeout in seconds for HTTP requests (default: 15)
- `OLX_TOKEN_EXPIRATION` - Verification token expiration time in hours (default: 24)
- `OLX_QUEUE` - Queue for price check jobs (default: price-checks)
- `OLX_CHUNK_SIZE` - Chunk size for processing advertisements (default: 100)

## Queue Configuration

The application uses Laravel's queue system to process jobs in the background:

- Price check jobs are processed on the `price-checks` queue
- Email notifications are processed on the default queue
- Failed jobs are stored in the `failed_jobs` table for later inspection

To monitor the queue:
```bash
./vendor/bin/sail artisan queue:listen --queue=price-checks,default
```

Or to view failed jobs:
```bash
./vendor/bin/sail artisan queue:failed
```

## License

[MIT License](LICENSE)
