FROM wordpress:latest

USER root

# change root password to `ubuntu`
RUN echo 'root:ubuntu' | chpasswd

# Enable SSH without authentication on the wordpress container                  
RUN apt-get update -qq && \
    apt-get install -yqq openssh-server telnet vim

# workdir for ssh
RUN mkdir -p /run/sshd

# generate server keys
RUN ssh-keygen -A

# Run sshd from WP container, to allow driving WP-CLI from JS tests
ADD ./conf/sshd_config /etc/ssh/sshd_config
EXPOSE 22
CMD ["/usr/sbin/sshd", "-D", "-o", "ListenAddress=0.0.0.0", "&"]

