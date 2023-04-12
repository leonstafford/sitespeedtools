#!/bin/bash

echo "build our WordPress, MySQL and Playwright containers"
./build-containers.sh
echo "launching just the WordPress, MySQL and WP-CLI containers"
docker-compose up -d wordpress db wpcli
echo "use Playwright to complete WordPress installation"
docker-compose run --rm tester node setup-wp.js


#   
#   
#   # debug
#   exit 1
#   
#   echo "shouldn't make it here"
#   
#    
#   docker-compose exec wordpress /bin/bash -c "wp --allow-root user list"
#   
#   docker-compose run --rm tester node test-plugin.js

