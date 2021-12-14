#!/bin/sh

docker stop api
docker stop elastic
docker rm elastic
docker rm api
docker stop httpd
docker rm httpd

mkdir -p /data/prefixcommons
cd /data/prefixcommons
git clone https://github.com/amalic/webapp-es-ang.git
git clone https://github.com/prefixcommons/data-ingest.git

docker run --detach \
    --name elastic \
    --env LOG4J_FORMAT_MSG_NO_LOOKUPS=true \
    --env VIRTUAL_HOST=elastic.prefixcommons.org \
    --env VIRTUAL_PORT=9200 \
    --env LETSENCRYPT_HOST=elastic.prefixcommons.org \
    --env LETSENCRYPT_VIRTUAL_PORT=9200 \
    --env LETSENCRYPT_EMAIL=alexander.malic@maastrichtuniversity.nl \
    --env discovery.type=single-node \
    --env http.cors.enabled=true \
    --env http.cors.allow-origin="*" \
    --publish 9200:9200 \
    --volume /data/prefixcommons/elastic:/usr/share/elasticsearch/data \
    --restart unless-stopped \
    docker.elastic.co/elasticsearch/elasticsearch:6.8.21
    # aqlx86/elasticsearch-cors

docker run --detach \
    --name httpd \
    --env VIRTUAL_HOST=prefixcommons.org \
    --env LETSENCRYPT_HOST=prefixcommons.org \
    --env LETSENCRYPT_EMAIL=alexander.malic@maastrichtuniversity.nl \
    --volume /data/prefixcommons/webapp-es-ang/html:/usr/local/apache2/htdocs/ \
    --restart unless-stopped \
    httpd

docker run --detach \
    --name api \
    --env VIRTUAL_HOST=api.prefixcommons.org \
    --env VIRTUAL_HOST=api.prefixcommons.org \
    --env LETSENCRYPT_HOST=api.prefixcommons.org \
    --env LETSENCRYPT_EMAIL=alexander.malic@maastrichtuniversity.nl \
    --restart unless-stopped \
    --link elastic:elastic \
    umids/prefixcommons-api

cd ./webapp-es-ang/es
cp ../../data-ingest/json/lsregistry.json .

while ! echo exit | nc localhost 9200; do sleep 1; done

curl -s -XDELETE http://localhost:9200/prefixcommons > /dev/null
curl -s -XPUT http://localhost:9200/prefixcommons -d  @mappings.json > /dev/null
curl -s -XPUT http://localhost:9200/_bulk?pretty --data-binary @lsregistry.json > /dev/null


docker kill prefixcommons_apidocs_redirect

docker run -d --rm --name=prefixcommons_apidocs_redirect -e SERVER_REDIRECT=smart-api.info/ui/886728821048533f67fe6df7adf5a526#/ -e VIRTUAL_HOST=apidocs.prefixcommons.org schmunk42/nginx-redirect