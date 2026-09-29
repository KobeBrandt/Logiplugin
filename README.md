# Logi Plugin API

An unofficial, read-only REST API for browsing plugins from the [Logi Marketplace](https://marketplace.logi.com).

The marketplace has no public API. This Laravel app reads the same data the marketplace website uses, normalizes it, caches it, and serves it as clean JSON with search, filtering and pagination.

A hosted instance with interactive docs runs at **https://logiplugin.com**.

> **Disclaimer:** This project is not affiliated with, endorsed by, or sponsored by Logitech. "Logi" and "Logitech" are trademarks of Logitech International S.A. The API depends on the marketplace's internal website data and may break if that changes.

## Endpoints

All endpoints are under `/api/v1` and return JSON.

| Method | Path | Description |
| ------ | ---- | ----------- |
| `GET` | `/api/v1/plugins` | List plugins (paginated) |
| `GET` | `/api/v1/plugins/{name}` | Get one plugin with full details (name is case-insensitive) |

### Query parameters for `/plugins`

| Parameter | Description |
| --------- | ----------- |
| `search` | Case-insensitive match on the plugin name or display name |
| `platform` | Only plugins that support this platform |
| `category` | Only plugins in this category |
| `page` | Page number (default `1`) |
| `per_page` | Results per page (default `25`, max `100`) |

Example:

```bash
curl "https://logiplugin.com/api/v1/plugins?search=spotify&per_page=10"
```

Responses are cached on the server (30 minutes by default) and sent with `Cache-Control: public, max-age=300` plus an `ETag`.

## Running locally

Requirements: PHP 8.4+ and Composer, or Docker.

### With PHP

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

The API does not need a database. If you don't run the MySQL container, set `CACHE_STORE=file` in `.env`.

### With Docker

```bash
cp .env.example .env
docker compose up -d
docker compose exec php-web composer install
docker compose exec php-web php artisan key:generate
docker compose exec php-web php artisan migrate
```

The app is then available at http://localhost:8080.

## Configuration

| Variable | Default | Description |
| -------- | ------- | ----------- |
| `LOGI_MARKETPLACE_BASE_URL` | `https://marketplace.logi.com` | Marketplace to read from |
| `LOGI_MARKETPLACE_BUILD_ID` | *(auto-detected)* | Override the marketplace's Next.js build ID. Leave empty; it is detected automatically and refreshed when it changes. |
| `LOGI_MARKETPLACE_CACHE_TTL` | `1800` | Seconds to cache marketplace responses |

## Tests

```bash
php artisan test
```

## Deployment

See [DEPLOY.md](DEPLOY.md) for the production setup (Docker + Caddy with automatic HTTPS).

## License

[MIT](LICENSE)
