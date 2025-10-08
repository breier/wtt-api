# Website Traffic Tracker Backend

Backend API for collecting unique web traffic tracking events ("visits") anonymised.
A JS client script obtains a long-lived rotating token, then submits visit events
(URL + fingerprint + client time stamp).

The API enforces:
* Per‑IP rate limiting
* Visits per page per day
* Token rotation & minimal TTL refresh logic

## Data Model
`Visit` fields:
* `id` (int, PK)
* `request_url` (varchar 255, truncated)
* `fp_hash` (varchar 128, truncated)
* `client_ts` (nullable datetime) – derived from epoch/int or string
* `created_at` (datetime immutable, auto set in constructor)

Indexes optimize lookups by `(request_url, fp_hash)`.

## Containers
`Services` list:
* `app` – PHP 8.4 FPM + Nginx (port 27300 -> 80)
* `database` – MySQL 8.0 (persisted in `database_data` volume)
* `redis` – Redis 7 (api/system cache)

## Setting up environment

> If it's for production, replace "localhost" in the `./build/vhost.conf` with the FQDN

```bash
docker compose up -d
cp .env.example .env # configure env variables
docker compose exec app composer install --no-dev
docker compose exec app /bin/console doctrine:migrations:migrate
```

## Testing
Run tests (inside container or with local PHP 8.4 + extensions):
```bash
docker compose exec app composer install
docker compose exec app /vendor/bin/phpunit
```
