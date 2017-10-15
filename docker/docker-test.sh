#!/usr/bin/env bash
#
# Convenience script for testing docker builds
#
set -e

docker build --tag sep:dev .

# Override entrypoint for debugging
#docker run --rm -it --entrypoint /bin/bash sep:dev

# Typical usage
docker run --rm --name sep_dev sep:dev
