#!/bin/bash

echo "build our WordPress, MySQL and Playwright containers"
./build-containers.sh
echo "launch the WordPress and MySQL containers"
docker-compose up -d db wordpress
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

