# docker-facturascripts
FacturaScripts official Docker image.

## Requirements
* **mysql-server**. You can use [official Docker mysql image](https://hub.docker.com/_/mysql) or use your currently mysql server installation.
​
## Build your image
```
$ git clone https://github.com/FacturaScripts/docker-facturascripts.git
$ cd docker-facturascripts
$ docker build -t facturascripts:latest .
```