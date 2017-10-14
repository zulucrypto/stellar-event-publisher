#!/usr/bin/env bash
#
# Example of launching a monitoring container that reads from a remote
# configuration file
#
set -e

docker run \
    -e SEP_CONFIG_FILE="http://example.com/stellar-event-publisher-config.json" \
    --name stellar_event_publisher \
    zulucrypto/stellar-event-publisher