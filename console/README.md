# Console mount

The Laravel package serves the data. The Ember engine that draws the Fleetbase pages is not in this repository yet. Until that package exists, "one console" stops at these authenticated JSON routes:

- `GET /int/v1/kazispace/vehicles/{vehicleId}/snapshot`
- `GET /int/v1/kazispace/vehicles/{vehicleId}/samples`
- `GET /int/v1/kazispace/alerts`
- `PATCH /int/v1/kazispace/alerts/{alertId}` with `{ "status": "open" | "ack" | "closed" }`

The engine reads `company_uuid` or `company_id` from the logged-in Fleetbase user. It does not trust an organization id sent by the browser.

Pages to mount in the console sidebar:

1. Fleet battery overview, backed by the alerts list plus each vehicle snapshot.
2. Single-vehicle battery page, backed by snapshot and samples.
3. Alert list, including the status update above.

Do not put decode tables, model weights, or prompt text in this UI package. The pages display fields already stored by `POST /kazispace/v1/ingest`.
