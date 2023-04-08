FROM mcr.microsoft.com/playwright:v1.19.0-focal

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY test-plugin.js ./

