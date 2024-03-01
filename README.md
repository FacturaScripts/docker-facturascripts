# docker-facturascripts
FacturaScripts official Docker image. More info at https://facturascripts.com/descargar/docker

## Run
```
docker run -d --name facturascripts -p 80:80 facturascripts/facturascripts:latest
```

## FacturaScripts + MySQL + adminer
Use the docker-compose.yml file to get FacturaScripts with a MySQL container (mysql) and adminer.
```
docker-compose up
```

## Build
```
docker build -t facturascripts/facturascripts:latest .
```

### Publish
```
docker login
docker push facturascripts/facturascripts:latest
```

### Multi-arch
```
docker buildx build --platform linux/amd64,linux/arm/v7,linux/arm64/v8 --push -t facturascripts/facturascripts:latest .
docker buildx build --platform linux/amd64,linux/arm/v7,linux/arm64/v8 --push -t facturascripts/facturascripts:XXX .
```

### Publish a new tag
```
docker build -t facturascripts/facturascripts:XXX .
docker tag IMAGE_ID facturascripts/facturascripts:XXX
docker push facturascripts/facturascripts:XXX
```
