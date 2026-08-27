# docker-facturascripts
FacturaScripts official Docker image. More info at https://facturascripts.com/descargar/docker

## Run
```
docker run -d --name facturascripts -p 80:80 facturascripts/facturascripts:latest
```

## FacturaScripts + MySQL + adminer
Use the docker compose.yml file to get FacturaScripts with a MySQL container (mysql) and adminer.

```
docker compose up
```

## Build
```
docker build -t facturascripts/facturascripts:latest .
```

### Release script
`release.sh` builds and pushes the multi-arch images for both channels in one go. It checks that
the versions exist on facturascripts.com, sets up the buildx builder and pushes:

* stable -> `<version>` and `latest`
* beta -> `<version>` and `beta`

```
./release.sh --stable 2026.5 --beta 2026.6
```

Use `--dry-run` to see the commands without running them, `--bump` to also update the
`ARG FS_VERSION` default in the Dockerfile, and `--help` for the rest of the options.
Either channel can be released on its own with just `--stable` or just `--beta`.

### Publish
```
docker login
docker push facturascripts/facturascripts:latest
```

### Multi-arch
You need buildx and qemu to build multi-arch images.

On Ubuntu 24.04 and older (or Debian):

```
sudo apt install -y docker-buildx qemu-user-static binfmt-support
```

On Ubuntu 26.04 and newer, `qemu-user-static` was split into `qemu-user` + `qemu-user-binfmt`:

```
sudo apt install -y docker-buildx qemu-user-binfmt binfmt-support
```

Alternatively, register the qemu handlers with a container instead of installing them on the host
(you still need `docker-buildx`):

```
docker run --privileged --rm tonistiigi/binfmt --install all
```

Then create a builder, because the `default` one cannot build multi-arch images nor use `--push`:

```
docker buildx create --name multiarch --driver docker-container --use
docker buildx inspect --bootstrap
```

The `Platforms:` line must list `linux/amd64`, `linux/arm64` and `linux/arm/v7`. Now you can build:

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
