# docker-facturascripts
FacturaScripts official Docker image. More info at https://facturascripts.com/descargar/docker

## Run
```
$ docker run -d --name facturascripts -p 80:80 facturascripts/facturascripts:latest
```

## Requirements
**mysql-server** is required. You can use the MySQL official docker image:
```
$ docker pull mysql
$ docker run --name mysql -e MYSQL_ROOT_PASSWORD=mypassword -d -p 3306:3306 mysql:latest

```

### Install FacturaScripts
When installing FacturaScripts, remember that if you use a mysql container, the MySQL host is the IP of the machine (or the name of the container) and not localhost.
​
## Build your image
You can get the source file and build your own image.
```
$ git clone https://github.com/FacturaScripts/docker-facturascripts.git
$ cd docker-facturascripts
$ docker build -t facturascripts:latest .
```
