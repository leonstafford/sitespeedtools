#!/bin/bash

# build our WordPress, MySQL and Playwright containers
./build-containers.sh
# launch the WordPress and MySQL containers
docker-compose up -d db wordpress
# use Playwright to complete WordPress installation
docker-compose run --rm tester node setup-wp.js

# In order to execute WP-CLI commands from Playwright, we'll setup 
# SSH connectivity from "tester" to "wordpress"

# generate public key on testing container (don't rm the container!)
docker-compose run tester sh -c 'ssh-keygen -t rsa -N "" -f ~/.ssh/id_rsa'

# save pub key from tester container to wordpress' authorized_keys
TESTER_PUB_KEY=$(docker-compose exec tester sh -c 'cat ~/.ssh/id_rsa.pub')

# confirm public key was captured
cat "$TESTER_KEY"

# enable SSH server on wordpress container
docker-compose run wordpress bash -c "apt-get update && apt-get install -y openssh-server"
# all "tester" container to SSH into the "wordpress" container via public key authentication
docker-compose run wordpress bash -c "mkdir ~/.ssh && tee $TESTER_KEY > ~/.ssh/authorized_keys"
# start the SSH service
docker-compose run wordpress bash -c "service start ssh"

# verify SSH connectivity between containers
docker-compose run tester sh -c 'ssh root@wordpress -c "hostname"'


# install WP-CLI so we can use it via ssh2
docker-compose exec wordpress /bin/bash -c "curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
 chmod +x wp-cli.phar && \
 mv wp-cli.phar /usr/local/bin/wp && \
 wp --allow-root --info"
 
docker-compose exec wordpress /bin/bash -c "wp --allow-root user list"

docker-compose run --rm tester node test-plugin.js

