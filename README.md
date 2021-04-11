# docker-facturascripts
FacturaScripts official Docker image. More info at https://facturascripts.com/descargar/docker

## Run
```
$ docker run -d --name facturascripts -p 80:80 facturascripts/facturascripts:latest
```

## FacturaScripts + MySQL + adminer
Use the docker-compose.yml file to get FacturaScripts with a MySQL container (mysql) and adminer.
```
$ docker-compose up
```

## ARM / Apple Silicon (M1)
For ARM or Apple Silicon platform use the dev-mariadb or dev-postgres composer files.