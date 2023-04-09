#!/bin/bash

echo "build our WordPress, MySQL and Playwright containers"
./build-containers.sh
echo "launch the WordPress and MySQL containers"
docker-compose up -d db wordpress
echo "use Playwright to complete WordPress installation"
docker-compose run --rm tester node setup-wp.js

# In order to execute WP-CLI commands from Playwright, we'll setup 
# SSH connectivity from "tester" to "wordpress"

# echo "Generating public key on testing container (don't remove the container!)"
# docker-compose run tester sh -c 'ssh-keygen -t rsa -N "" -f ~/.ssh/id_rsa'

# echo "Saving public key from tester container as variable"
# # TESTER_PUB_KEY=$(docker-compose exec -T tester sh -c 'cat ~/.ssh/id_rsa.pub')
# docker-compose run tester sh -c 'cat ~/.ssh/id_rsa.pub > /tmp/pubkey.txt'
# TESTER_PUB_KEY=$(docker-compose exec tester sh -c 'cat /tmp/pubkey.txt')
# 
# echo "Confirming public key was captured"
# echo "$TESTER_PUB_KEY"

echo "enable SSH without authentication on the wordpress container"

docker-compose exec wordpress bash -c "\
apt-get update -qq && \
apt-get install -yqq openssh-server && \
echo -e '\nPasswordAuthentication no\nChallengeResponseAuthentication no\nUsePAM no\nPermitEmptyPasswords yes' >> /etc/ssh/sshd_config && \
service ssh restart"

echo "Committing changes to a new image to temporarily persist state"
docker commit sitespeedtools_wordpress_1 my-wordpress-with-ssh

docker ps
docker image ls | grep wordpress

echo "Waiting to ensure commit completes"
sleep 5

docker ps
docker image ls | grep wordpress

echo "Shut down original WP container"
# docker-compose down
sudo docker stop $(sudo docker ps | grep "sitespeedtools_wordpress_1" | cut -d " " -f 1)

echo "running new container accepting SSH without auth"
docker run \
  --name sitespeedtools_wordpress_1 \
  -d \
  -p 8000:80 \
  -v sitespeedtools:/var/www/html/wp-content/plugins/sitespeedtools \
  -v wp_data:/var/www/html \
  --env WORDPRESS_DB_HOST=db:3306 \
  --env WORDPRESS_DB_USER=wordpress \
  --env WORDPRESS_DB_PASSWORD=wordpress \
  --env WORDPRESS_DB_NAME=wordpress \
  my-wordpress-with-ssh:latest



# # all "tester" container to SSH into the "wordpress" container via public key authentication
# docker-compose run wordpress bash -c "mkdir -p ~/.ssh && echo '$TESTER_PUB_KEY' >> ~/.ssh/authorized_keys"
# # start the SSH service
# docker-compose run wordpress bash -c "service start ssh"

# verify SSH connectivity between by tester SSH'ing to wordpress and printing hostname
docker-compose run tester sh -c 'ssh -tt root@wordpress "hostname"'


# debug
exit 1



# install WP-CLI so we can use it via ssh2
docker-compose exec wordpress /bin/bash -c "curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
 chmod +x wp-cli.phar && \
 mv wp-cli.phar /usr/local/bin/wp && \
 wp --allow-root --info"
 
docker-compose exec wordpress /bin/bash -c "wp --allow-root user list"

docker-compose run --rm tester node test-plugin.js

