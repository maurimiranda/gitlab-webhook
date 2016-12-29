#!/bin/bash
cd /var/www/dashboard
git checkout $1
git pull
yarn install
yarn run build
