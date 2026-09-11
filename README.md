# phpwol

Simple PHP app to monitor LAN devices and send Wake-on-LAN magic packets.

## Requirements

- Docker and Docker Compose
- Linux host (host networking is used so WOL broadcasts reach your LAN)

## Quick start

```bash
cp .env.example .env
# optional: set APP_PASSWORD=... in .env
docker compose up --build -d
```

Open [http://localhost:9080](http://localhost:9080).

Data (SQLite DB + settings) is stored in `./data` on the host.

## Configuration

| Variable | Default | Description |
|---|---|---|
| `APP_PASSWORD` | _(empty)_ | If set, requires login. Leave empty for LAN-only trust. |
| `WOL_BROADCAST` | `255.255.255.255` | UDP broadcast address for magic packets |
| `DATA_DIR` | `/var/www/html/data` | Path inside the container for SQLite + settings |
| `HTTP_PORT` | `9080` | Nginx listen port (host network) |

The container uses `network_mode: host` and `cap_add: [NET_RAW]` so ICMP ping and WOL UDP broadcasts work.

## Stack

- PHP 8.5-FPM + Nginx (single container via supervisord)
- SQLite (PDO, prepared statements)
- Bootstrap 5.3 + jQuery 4
- Pure PHP magic packets (no `wakeonlan` binary)

## Security notes

- Prefer setting `APP_PASSWORD` if the UI is reachable beyond a trusted LAN.
- Wake/status APIs only operate on devices stored in the database.
- Do not expose PHP-FPM port 9000; Nginx listens on **9080** by default.
