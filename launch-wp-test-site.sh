#!/bin/bash

set -e

echo "build our WordPress, MySQL and Playwright containers"
./build-containers.sh
echo "launching just the WordPress, MySQL and WP-CLI containers"
docker-compose up -d wordpress db wpcli pma
echo "use Playwright to complete WordPress installation"
docker-compose build tester

# setup a new WordPress site
docker-compose run --rm tester node setup-wp.js || exit 1

echo "New WP site ready for testing Site Speed Tools at http://localhost:80"
