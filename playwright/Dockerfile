FROM mcr.microsoft.com/playwright:v1.19.0-focal

# sshpass in case of needing to run any WP-CLI cmds on the WP container
RUN apt-get update -qq && \
    apt-get install -yqq telnet vim sshpass

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY setup-wp.js ./
COPY test-plugin.js ./

RUN mkdir /app/videos

