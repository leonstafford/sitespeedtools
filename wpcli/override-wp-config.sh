#!/bin/bash

# Exit the script immediately if a command exits with a non-zero status
set -e

# Define the hardcoded database credentials
DB_NAME="wordpress"
DB_USER="wordpress"
DB_PASSWORD="wordpress"
DB_HOST="db:3306"

# Replace the getenv_docker() function calls with the hardcoded credentials in wp-config.php
sed -i "s/getenv_docker('WORDPRESS_DB_NAME', 'wordpress')/'${DB_NAME}'/g" /var/www/html/wp-config.php
sed -i "s/getenv_docker('WORDPRESS_DB_USER', 'example username')/'${DB_USER}'/g" /var/www/html/wp-config.php
sed -i "s/getenv_docker('WORDPRESS_DB_PASSWORD', 'example password')/'${DB_PASSWORD}'/g" /var/www/html/wp-config.php
sed -i "s/getenv_docker('WORDPRESS_DB_HOST', 'mysql')/'${DB_HOST}'/g" /var/www/html/wp-config.php