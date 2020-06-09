# prefixcommons-api
API for prefixcommons

Used [Swagger Editor](http://editor.swagger.io/) to develop the [API specification](https://github.com/prefixcommons/prefixcommons-api/blob/master/prefixcommons-swagger-api-definition.yaml)

Code stub was generated with [Swagger CodeGen](https://github.com/swagger-api/swagger-codegen/)
```
 java -jar swagger-codegen-cli.jar generate -i prefixcommons-swagger-api-definition.yaml -l slim -o slim-server
```

Modified the composer.json to include MonoLog and ElasticSearch libraries

Code currently in index.php file

## Deploy prefixcommons API

Use the start.sh scripts to deploy on node2 (will use `/data/prefixcommons` as working folder):

```bash
./start.sh
```

> It will create the required folders, start docker containers and init database

## Docker compose

Docker compose is not finished

The goal is to avoid needing to perform bash operations by preparing the httpd and elasticsearch images