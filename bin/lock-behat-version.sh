#!/usr/bin/env bash
set -euo pipefail

BEHAT_VERSION=$1

jq --arg v "${BEHAT_VERSION}" '
  .["require-dev"]["behat/behat"] = $v
' < composer.json > composer.json.tmp && mv composer.json.tmp composer.json
