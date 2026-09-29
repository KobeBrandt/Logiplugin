# Deploying to logiplugin.com

Production runs on an OVHcloud VPS with Docker, using `docker-compose.prod.yml`:

- **app**: PHP/Apache with the code baked into the image (no database, file cache)
- **caddy**: reverse proxy that gets and renews HTTPS certificates for `logiplugin.com` automatically, and redirects `www.logiplugin.com` to it

The regular `docker-compose.yml` is for local development only.

## One-time setup

1. **DNS**: at your domain registrar, create these records pointing to the VPS IP:
   - `A` record for `logiplugin.com` → VPS IPv4
   - `A` record for `www.logiplugin.com` → VPS IPv4
   - (optional) matching `AAAA` records if the VPS has IPv6

   Make sure the records resolve (`dig +short logiplugin.com`) before starting Caddy, otherwise certificate issuance fails.

2. **Firewall**: allow ports 22, 80 and 443 (TCP, plus 443/UDP for HTTP/3), both in `ufw` (if enabled) and in the OVHcloud network firewall (if enabled).

3. **Docker**: install Docker Engine + the compose plugin (https://docs.docker.com/engine/install/).

4. **Code and config**:
   ```bash
   git clone <repo-url> /opt/logiplugin
   cd /opt/logiplugin
   cp .env.production.example .env.production
   echo "APP_KEY=base64:$(openssl rand -base64 32)"   # paste into .env.production
   ```

5. **Start**:
   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

## Deploying updates

```bash
cd /opt/logiplugin && ./deploy.sh
```

This pulls the latest code, rebuilds the image and restarts the containers. Config, route and view caches are rebuilt when the container starts.

## Useful commands

```bash
docker compose -f docker-compose.prod.yml logs -f app     # application logs
docker compose -f docker-compose.prod.yml logs -f caddy   # certificate / proxy logs
docker compose -f docker-compose.prod.yml exec app php artisan cache:clear
```
