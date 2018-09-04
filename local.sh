#!/bin/bash

# Tear down all existing services 
docker-compose down --rmi all;

# Rebuild the services
docker-compose up -d;