# Console mount

The Laravel package serves the data. The Ember engine that draws the Fleetbase pages is not in this repository yet. Until that package exists, the console stops at these session routes. They use the `fleetbase.protected` middleware, the same group as other Fleetbase `int/v1` routes.

- `GET /int/v1/kazispace/snapshots`
- `GET /int/v1/kazispace/vehicles/{vehicleId}/snapshot`
- `GET /int/v1/kazispace/vehicles/{vehicleId}/samples?from=YYYY-MM-DDTHH:MM:SSZ&to=YYYY-MM-DDTHH:MM:SSZ`
- `GET /int/v1/kazispace/alerts`
- `PATCH /int/v1/kazispace/alerts/{alertId}` with `{ "status": "open" | "ack" | "closed" }`

The organization is `User.company_uuid` only. `company_id` is not a fallback. The browser cannot pass an organization id. A row written with a different `organization_id` is not returned.

`snapshots` lists one current snapshot per vehicle in that company, for the fleet overview.

`samples` is the curve. It returns the downsampled columns already stored in MySQL: `observed_at`, `soc`, `soh`, `pack_voltage`, `temp_max`, `speed`. It does not return raw frames. `from` and `to` are inclusive. Omit both and the window is the last 24 hours. A window is not truncated at 500 points. A bad timestamp or a reversed window is `422`.

Pages to mount in the console sidebar:

1. Fleet battery overview, backed by `snapshots` plus the alerts list.
2. Single-vehicle battery page, backed by snapshot and samples.
3. Alert list, including the status update above.

Do not put decode tables, model weights, or prompt text in this UI package. The pages display fields already stored by `POST /kazispace/v1/ingest`.
