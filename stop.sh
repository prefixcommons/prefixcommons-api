#!/bin/sh

docker stop api
docker stop elastic
docker rm elastic
docker rm api
docker stop httpd
docker rm httpd
