#!/bin/bash

./build-containers.sh
docker-compose up -d db wordpress
docker-compose run --rm tester node setup-wp.js
