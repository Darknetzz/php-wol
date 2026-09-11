# phpwol

Simple PHP app to monitor LAN devices and send Wake-on-LAN magic packets.

## Requirements

- Docker and Docker Compose
- Linux host (host networking is used so WOL broadcasts reach your LAN)

## Quick start

```bash
cp .env.example .env
# optional: set APP_PASSWORD=... in .env
docker compose up -d
```

This pulls [`darknetz/php-wol`](https://hub.docker.com/r/darknetz/php-wol) (`latest`). To rebuild from source instead: `docker compose up --build -d`.

Open [http://localhost:9080](http://localhost:9080) (Docker) or [http://web01/wol](http://web01/wol) when served by the host Apache under `/var/www/html/wol`.

Data (SQLite DB + settings) is stored in `./data` on the host and is shared by both entrypoints.

### Image tags

| Tag | Description |
|---|---|
| `latest` | Latest release |
| `2.1.0` | Specific version |

```bash
docker pull darknetz/php-wol:2.1.0
```

Maintainers: publish a new version locally with `./scripts/docker-publish.sh <version>` (requires Docker Hub login as `darknetz`).

## Configuration

| Variable | Default | Description |
|---|---|---|
| `APP_PASSWORD` | _(empty)_ | If set, requires login. Leave empty for LAN-only trust. |
| `WOL_BROADCAST` | `255.255.255.255` | UDP broadcast address for magic packets |
| `DATA_DIR` | `/var/www/html/data` | Path inside the container for SQLite + settings |
| `HTTP_PORT` | `9080` | Nginx listen port (host network / Docker) |
| `BASE_PATH` | _(auto)_ | URL prefix. Unset = auto. Empty = domain root (NPM subdomain). `/wol` for `http://web01/wol` |

### Reverse proxy (NPMPlus)

Preferred: point the proxy host at the Docker app (no path):

- **Forward Hostname / IP:** `10.0.2.55` (not `10.0.2.55/wol/`)
- **Forward Port:** `9080`

If you instead forward to Apache `10.0.2.55:80/wol/`, the app treats non-local hosts (e.g. `wol.roste.org`) as domain-root and emits `/assets/...` links so CSS loads. `http://web01/wol` still uses the `/wol` prefix.

The container uses `network_mode: host` and `cap_add: [NET_RAW]` so ICMP ping and WOL UDP broadcasts work. Host Apache uses the same PHP app via `.htaccess` rewriting into `public/`.

## Stack

- PHP 8.5-FPM + Nginx (single container via supervisord)
- SQLite (PDO, prepared statements)
- Bootstrap 5.3 + jQuery 4
- Pure PHP magic packets (no `wakeonlan` binary)

## Security notes

- Prefer setting `APP_PASSWORD` if the UI is reachable beyond a trusted LAN.
- Wake/status APIs only operate on devices stored in the database.
- Do not expose PHP-FPM port 9000; Nginx listens on **9080** by default.
