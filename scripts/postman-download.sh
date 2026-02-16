#!/usr/bin/env bash
set -euo pipefail

if [ -f storage/app/private/scribe/collection.json ]; then
  cp storage/app/private/scribe/collection.json postman/scribe.postman.json
  exit 0
fi

if [ -f storage/app/scribe/collection.json ]; then
  cp storage/app/scribe/collection.json postman/scribe.postman.json
  exit 0
fi

if [ -f public/docs/collection.json ]; then
  cp public/docs/collection.json postman/scribe.postman.json
  exit 0
fi

echo "Scribe collection.json not found. Run php artisan scribe:generate first."
exit 1
