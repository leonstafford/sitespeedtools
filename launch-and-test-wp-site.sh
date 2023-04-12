#!/bin/bash

set -e

echo "build our WordPress, MySQL and Playwright containers"
./build-containers.sh
echo "launching just the WordPress, MySQL and WP-CLI containers"
docker-compose up -d wordpress db wpcli 
echo "use Playwright to complete WordPress installation"
docker-compose build tester

# setup a new WordPress site
docker-compose run --rm tester node setup-wp.js || exit 1

# run any WP-CLI commands
#  docker-compose exec wordpress /bin/bash -c "wp --allow-root user list"

# run plugin tests
docker-compose run --rm tester node test-plugin.js || exit 1

