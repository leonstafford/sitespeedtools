#!/bin/bash

# start sshd
/usr/sbin/sshd -D -e -f /etc/ssh/sshd_config &

# start apache2
/usr/sbin/apache2ctl -D FOREGROUND &

# # wait for any process to exit
wait -n
# 
# # return first to exit's code
exit $?
