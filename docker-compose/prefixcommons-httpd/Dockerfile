FROM httpd

MAINTAINER Alexander Malic <alexander.malic@maastrichtuniversity.nl>

RUN apt-get update \
  && apt-get install -y git netcat curl \
  && cd /opt \
  && git clone https://github.com/amalic/webapp-es-ang.git \
  && cp -r ./webapp-es-ang/html/* /usr/local/apache2/htdocs/ \
  && git clone https://github.com/prefixcommons/data-ingest.git \
  && cd ./webapp-es-ang/es \
  && mv ../../data-ingest/json/lsregistry.json . \
  && while ! echo exit | netcat es 9200; do sleep 1; done \
  && curl -XDELETE http://es:9200/prefixcommons > /dev/null \
  && curl -XPUT http://es:9200/prefixcommons -d  @mappings.json > /dev/null \
  && curl -XPUT http://es:9200/_bulk?pretty --data-binary @lsregistry.json > /dev/null
