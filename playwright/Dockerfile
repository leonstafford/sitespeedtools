FROM mcr.microsoft.com/playwright:v1.19.0-focal

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY setup-wp.js ./
COPY test-plugin.js ./

RUN mkdir /app/videos

