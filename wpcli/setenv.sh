#!/bin/bash

# manually set env vars, so that incoming SSH connections have the expected env vars
export WORDPRESS_DB_NAME="wordpress"
export WORDPRESS_DB_USER="wordpress"
export WORDPRESS_DB_PASSWORD="wordpress"
export WORDPRESS_DB_HOST="db:3306"

# run in interactive mode, so that the ssh2 library can use the terminal
exec /bin/bash -i