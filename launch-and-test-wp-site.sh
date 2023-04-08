#!/bin/bash

docker-compose up -d db wordpress
docker-compose run --rm tester node setup-wp.js
docker-compose run --rm tester node test-plugin.js
