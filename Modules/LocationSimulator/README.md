# LocationSimulator

Local driver location simulator. It is the producer side of the location pipeline: simulated drivers move along
pre-fetched Kyiv routes and publish their positions to the Kafka topic `driver.location.updated`
(`kafka.topics.driver_location`), keyed by `driver_id`. The `LocationTracker` consumer reads them.

The module is self-contained. The only code it shares with the rest of the app is `App\Shared\Data\DriverLocation`
and the topic config.

## Run it

Start the simulator in Docker (it only starts when you ask for it, via the `simulator` Compose profile):

```bash
docker compose --profile simulator up simulator
```

It restarts automatically (`restart: unless-stopped`). Stop it with `docker compose --profile simulator stop simulator`
or Ctrl+C; it finishes the current tick, flushes the publisher and exits with code 0.

Run it by hand, or print messages without Kafka:

```bash
docker compose run --rm app php artisan simulator:drivers --dry-run --ticks=3
```

### `simulator:drivers` options

| Option | Default | Meaning |
| --- | --- | --- |
| `--drivers` | `10` | Number of simulated drivers (`drv_001`, `drv_002`, ...) |
| `--interval` | env `LOCATION_INTERVAL_SECONDS` (`5`) | Seconds between ticks |
| `--min-speed` | `30` | Minimum driver speed, km/h |
| `--max-speed` | `60` | Maximum driver speed, km/h |
| `--dry-run` | off | Print messages to the console, do not connect to Kafka |
| `--ticks` | `0` | Stop after N ticks (`0` runs until stopped); handy for dry runs |

Defaults live in `config/config.php`.

## Routes

Routes are JSON files in `resources/routes/`. Each file is an OSRM geometry: a list of `[lng, lat]` points
(longitude first). Five Kyiv routes are committed. The simulator never calls OSRM. To regenerate the files, run
this by hand (it calls the OSRM demo server once per route; the start/end pairs are in `config/config.php`):

```bash
docker compose run --rm app php artisan simulator:fetch-routes
```

## Behaviour

- Each driver gets a route (round robin) and its own random speed within `--min-speed`..`--max-speed`.
- Start positions are staggered along the routes.
- Every tick a driver moves `speed x interval` along its route, with interpolation between route points.
  Speed varies a little per tick, and the reported position has small GPS noise.
- Heading comes from the direction of travel.
- At the end of a route the driver turns around and drives back.

## Message format

```json
{
    "schema_version": 1,
    "driver_id": "drv_001",
    "lat": 50.4501,
    "lng": 30.5234,
    "heading": 90,
    "speed_kmh": 42.5,
    "accuracy_m": 6.5,
    "recorded_at": "2026-11-13T08:00:00+00:00"
}
```

## Tests

```bash
docker compose run --rm app php artisan test --compact Modules/LocationSimulator
```

No Kafka broker is needed: the publisher is tested with `Kafka::fake()`.
