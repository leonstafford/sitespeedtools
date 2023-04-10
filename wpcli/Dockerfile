FROM wordpress:cli

USER root

# Enable SSH without authentication on the wordpress container
RUN apk update && \
    apk add --no-cache openssh-server vim curl less


#    apk add --no-cache openssh-server telnet vim curl less supervisor


# change root password to `ubuntu`
RUN echo 'root:ubuntu' | chpasswd

# for run/exec/ssh sessions
RUN echo "alias wp='wp --allow-root'" >> /etc/profile.d/aliases.sh

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

CMD ["/usr/sbin/sshd", "-D", "-e", "-f", "/etc/ssh/sshd_config"]

