FROM ubuntu:latest

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -yqq tzdata && \
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

# we need to run both sshd and the WP image's built-in CMD ["apache2-foreground"]
CMD ["/usr/sbin/sshd", "-D", "-e", "-f", "/etc/ssh/sshd_config"]
# /usr/sbin/sshd -D -e -f /etc/ssh/sshd_config &

# CMD ["/run-sshd-and-apache2.sh"]
# CMD ["/usr/bin/supervisord"]
