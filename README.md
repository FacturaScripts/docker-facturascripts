# docker-facturascripts
FacturaScripts official Docker image. More info at https://facturascripts.com/descargar/docker

## Run
```
docker run -d -p 80:80 facturascripts:latest
```

## Requirements
**mysql-server**. You can use [official Docker mysql image](https://hub.docker.com/_/mysql) or use your currently mysql server installation.
​
## Build your image
```
$ git clone https://github.com/FacturaScripts/docker-facturascripts.git
$ cd docker-facturascripts
$ docker build -t facturascripts:latest .
```
