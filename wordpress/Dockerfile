FROM wordpress:latest

USER root

# change root password to `ubuntu`
RUN echo 'root:ubuntu' | chpasswd

# Enable SSH without authentication on the wordpress container                  
RUN apt-get update -qq && \
    apt-get install -yqq openssh-server telnet vim curl

# install WP-CLI so we can use it via ssh2
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
 chmod +x wp-cli.phar && \
 mv wp-cli.phar /usr/local/bin/wp && \
 wp --allow-root --info

RUN echo "alias wp='wp --allow-root'" >> ~/.bashrc

# workdir for ssh
RUN mkdir -p /run/sshd
RUN mkdir -p /var/run/sshd

# generate server keys
RUN ssh-keygen -A

# Run sshd from WP container, to allow driving WP-CLI from JS tests
ADD ./conf/sshd_config /etc/ssh/sshd_config

EXPOSE 22

CMD ["/usr/sbin/sshd", "-D", "-e", "-f", "/etc/ssh/sshd_config"]
