FROM ubuntu:latest

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y php php-mysql php-curl php-gd php-intl php-mbstring php-xml php-xmlrpc && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN apt-get update -qq && \
    apt-get install -yqq openssh-server telnet vim curl less supervisor

USER root

# change root password to `ubuntu`
RUN echo 'root:ubuntu' | chpasswd

WORKDIR  /

# install WP-CLI so we can use it via ssh2
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
 chmod +x wp-cli.phar && \
 mv wp-cli.phar /usr/local/bin/wp && \
 wp --allow-root --info

# for run/exec/ssh sessions
RUN echo "alias wp='wp --allow-root'" >> ~/.bashrc

WORKDIR  /var/www/html

# install WP first
# RUN /usr/local/bin/wp --allow-root core download

# symlink to nested path once WP files in place


# workdir for ssh
RUN mkdir -p /run/sshd
RUN mkdir -p /var/run/sshd

# generate server keys
RUN ssh-keygen -A

# Run sshd from WP container, to allow driving WP-CLI from JS tests
ADD ./conf/sshd_config /etc/ssh/sshd_config

# ADD ./run-sshd-and-apache2.sh /run-sshd-and-apache2.sh
# ADD ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 22 80

# Copy the script into the container
COPY override-wp-config.sh /override-wp-config.sh

# Make the script executable
RUN chmod +x /override-wp-config.sh

# Update the CMD to run the script and then execute the original CMD
CMD /bin/bash -c "/override-wp-config.sh && /usr/sbin/sshd -D -e -f /etc/ssh/sshd_config"
