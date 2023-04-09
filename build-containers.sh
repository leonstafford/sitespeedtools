#!/bin/bash

echo "Building containers..."
docker-compose build -q
echo "Containers built successfully."

