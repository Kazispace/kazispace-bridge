# kazispace-bridge

AGPL-3.0 bridge that lets [Fleetbase](https://github.com/fleetbase/fleetbase) store and render battery results produced by Kazispace.

This repository is the public half of the split described in the Kazispace Fleet PRD:

- **This repo:** accept a signed result, write MySQL, serve the console.
- **Kazispace Engine (private):** decode CAN frames, score battery health, diagnose faults, and run the AI agent. That code is not here and must not be added.

## Install into Fleetbase

From the Fleetbase API checkout, add this package and set the shared secret:

```bash
composer config repositories.kazispace-bridge vcs https://github.com/Kazispace/kazispace-bridge
composer require kazispace/bridge
php artisan vendor:publish --provider="Kazispace\\Bridge\\BridgeServiceProvider" --tag=kazispace-config
php artisan migrate
```

```dotenv
KAZISPACE_BRIDGE_HMAC_SECRET=replace-with-a-long-random-string
```

Use this exact name on the Engine as well.

The Engine signs each request with `KAZISPACE_BRIDGE_HMAC_SECRET`. This package reads the same variable. The Engine sends the exact raw body bytes.

## Ingest

`POST /kazispace/v1/ingest`

Headers:

- `X-Kazispace-Timestamp`: unix seconds
- `X-Kazispace-Signature`: hex HMAC-SHA256 of `timestamp + "." + raw body`

The body shape is in [`contract/result.example.json`](contract/result.example.json). `contract_version` must be `"1"`. Unknown algorithm internals are rejected by omission: only these result fields are stored.

A result is applied only when its `observed_at` is later than the stored snapshot. The comparison and the `timestamp` columns use UTC `Y-m-d H:i:s`. The snapshot row is locked and updated only when `observed_at` is still older. Curve points and the alert are written in that same transaction, and only when the snapshot moves forward. An older result returns `200` with `stored: true` and `snapshot_updated: false`, and does not change the curve or the alert.

Requests older than five minutes are rejected.

## Console

Authenticated Fleetbase users read results at `/int/v1/kazispace/...`. See [`console/README.md`](console/README.md).

## Tests

```bash
find src -name '*.php' -print0 | xargs -0 -n1 php -l
find tests -name '*_test.php' -print0 | xargs -0 -n1 php
```

Pull requests and pushes to `main` run the same commands in `.github/workflows/test.yml`.

## License

GNU Affero General Public License v3. See [LICENSE](LICENSE). If you modify this bridge and users reach that modification over the network, you must offer them the corresponding source.
