FROM wordpress:latest

# Enable SSH without authentication on the wordpress container                  
RUN apt-get update -qq && \
    apt-get install -yqq openssh-server telnet vim curl less

USER root

# change root password to `ubuntu`
RUN echo 'root:ubuntu' | chpasswd

VOLUME /var/www/html

WORKDIR  /var/www/html

# install WP-CLI so we can use it via ssh2
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
 chmod +x wp-cli.phar && \
 mv wp-cli.phar /usr/local/bin/wp && \
 wp --allow-root --info

# for run/exec/ssh sessions
RUN echo "alias wp='wp --allow-root'" >> ~/.bashrc

# still need full path
RUN /usr/local/bin/wp --allow-root core download

# workdir for ssh
RUN mkdir -p /run/sshd
RUN mkdir -p /var/run/sshd

# generate server keys
RUN ssh-keygen -A

# Run sshd from WP container, to allow driving WP-CLI from JS tests
ADD ./conf/sshd_config /etc/ssh/sshd_config

ADD ./run-sshd-and-apache2.sh /run-sshd-and-apache2.sh
ADD ./run-sshd-and-apache2.sh /run-sshd-and-apache2.sh

EXPOSE 22 80

# we need to run both sshd and the WP image's built-in CMD ["apache2-foreground"]
# CMD ["/usr/sbin/sshd", "-D", "-e", "-f", "/etc/ssh/sshd_config"]

CMD ["/run-sshd-and-apache2.sh"]
