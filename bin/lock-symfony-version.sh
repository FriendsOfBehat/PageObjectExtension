#!/usr/bin/env bash
set -euo pipefail

SYMFONY_VERSION=$1

jq --arg v "~${SYMFONY_VERSION}.0" '
  .["require-dev"] |= with_entries(.key as $k | if ($k | test("^symfony/")) then .value = $v else . end)
' < composer.json > composer.json.tmp && mv composer.json.tmp composer.json
