#!/bin/bash
#
# Compila y publica las imágenes Docker de FacturaScripts en Docker Hub.
#
#   ./release.sh --stable 2026.5 --beta 2026.6
#
# Etiquetas que se publican:
#   estable -> <version> + latest
#   beta    -> <version> + beta
#
set -euo pipefail

IMAGE="facturascripts/facturascripts"
PLATFORMS="linux/amd64,linux/arm64/v8,linux/arm/v7"
BUILDER="multiarch"
DOWNLOAD_URL="https://facturascripts.com/DownloadBuild/1"

STABLE=""
BETA=""
DRY_RUN=0
ASSUME_YES=0
BUMP=0

CWD="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

usage() {
	cat <<EOF
Uso: $(basename "$0") [opciones]

Opciones:
  -s, --stable VERSION   Versión estable. Publica <VERSION> y latest.
  -b, --beta VERSION     Versión beta. Publica <VERSION> y beta.
  -i, --image NOMBRE     Repositorio de Docker Hub (por defecto: $IMAGE).
  -p, --platforms LISTA  Plataformas (por defecto: $PLATFORMS).
      --builder NOMBRE   Builder de buildx (por defecto: $BUILDER).
      --bump             Actualiza el ARG FS_VERSION del Dockerfile a la estable.
  -n, --dry-run          Muestra los comandos sin ejecutarlos.
  -y, --yes              No pide confirmación antes de publicar.
  -h, --help             Muestra esta ayuda.

Ejemplos:
  $(basename "$0") --stable 2026.5 --beta 2026.6
  $(basename "$0") --stable 2026.6          # solo estable
  $(basename "$0") --beta 2026.7 --dry-run
EOF
}

die() { echo "Error: $*" >&2; exit 1; }
info() { echo ">> $*"; }

run() {
	if [ "$DRY_RUN" -eq 1 ]; then
		printf '   [dry-run]'; printf ' %q' "$@"; printf '\n'
	else
		"$@"
	fi
}

while [ $# -gt 0 ]; do
	case "$1" in
		-s|--stable)    [ $# -ge 2 ] || die "$1 necesita un valor"; STABLE="$2"; shift 2 ;;
		-b|--beta)      [ $# -ge 2 ] || die "$1 necesita un valor"; BETA="$2"; shift 2 ;;
		-i|--image)     [ $# -ge 2 ] || die "$1 necesita un valor"; IMAGE="$2"; shift 2 ;;
		-p|--platforms) [ $# -ge 2 ] || die "$1 necesita un valor"; PLATFORMS="$2"; shift 2 ;;
		--builder)      [ $# -ge 2 ] || die "$1 necesita un valor"; BUILDER="$2"; shift 2 ;;
		--bump)         BUMP=1; shift ;;
		-n|--dry-run)   DRY_RUN=1; shift ;;
		-y|--yes)       ASSUME_YES=1; shift ;;
		-h|--help)      usage; exit 0 ;;
		*)              usage >&2; die "opción desconocida: $1" ;;
	esac
done

[ -n "$STABLE" ] || [ -n "$BETA" ] || { usage >&2; die "indica al menos --stable o --beta"; }
[ -f "$CWD/Dockerfile" ] || die "no encuentro el Dockerfile en $CWD"

command -v docker >/dev/null || die "docker no está instalado"
docker buildx version >/dev/null 2>&1 || die "falta docker buildx (apt install docker-buildx)"
command -v curl >/dev/null || die "curl no está instalado"

# --- Comprobar que las versiones existen en facturascripts.com -----------------

verify_version() {
	local version="$1" label="$2" result code type
	result="$(curl -sIL --max-time 30 -o /dev/null -w '%{http_code} %{content_type}' \
		"$DOWNLOAD_URL/$version" || true)"
	code="${result%% *}"
	type="${result#* }"
	if [ "$code" != "200" ]; then
		die "la versión $label '$version' no existe (HTTP $code en $DOWNLOAD_URL/$version)"
	fi
	case "$type" in
		*zip*) ;;
		*) die "la versión $label '$version' no devuelve un zip (Content-Type: $type)" ;;
	esac
	info "versión $label $version disponible"
}

[ -n "$STABLE" ] && verify_version "$STABLE" "estable"
[ -n "$BETA" ] && verify_version "$BETA" "beta"

# --- Planificar los builds ----------------------------------------------------
# Si la estable y la beta son la misma versión, se compila una sola vez con
# las cuatro etiquetas en lugar de subir la misma imagen dos veces.

PLAN_VERSIONS=()
PLAN_TAGS=()

if [ -n "$STABLE" ] && [ "$STABLE" = "${BETA:-}" ]; then
	PLAN_VERSIONS+=("$STABLE")
	PLAN_TAGS+=("$STABLE latest beta")
else
	[ -n "$STABLE" ] && { PLAN_VERSIONS+=("$STABLE"); PLAN_TAGS+=("$STABLE latest"); }
	[ -n "$BETA" ] && { PLAN_VERSIONS+=("$BETA"); PLAN_TAGS+=("$BETA beta"); }
fi

echo
echo "Se van a compilar y PUBLICAR en Docker Hub:"
for i in "${!PLAN_VERSIONS[@]}"; do
	for tag in ${PLAN_TAGS[$i]}; do
		echo "  $IMAGE:$tag   (FS_VERSION=${PLAN_VERSIONS[$i]})"
	done
done
echo "  plataformas: $PLATFORMS"
echo

if [ "$DRY_RUN" -eq 0 ] && [ "$ASSUME_YES" -eq 0 ]; then
	read -r -p "¿Continuar? [s/N] " answer
	case "$answer" in
		s|S|y|Y|si|SI|Si|yes) ;;
		*) echo "Cancelado."; exit 1 ;;
	esac
fi

# --- Login en Docker Hub ------------------------------------------------------

if [ "$DRY_RUN" -eq 0 ]; then
	if ! docker system info 2>/dev/null | grep -q '^ Username:'; then
		info "no hay sesión en Docker Hub, ejecutando docker login"
		docker login
	fi
fi

# --- Preparar el builder multi-arquitectura -----------------------------------

if ! docker buildx inspect "$BUILDER" >/dev/null 2>&1; then
	info "creando el builder '$BUILDER'"
	run docker buildx create --name "$BUILDER" --driver docker-container
fi

info "arrancando el builder '$BUILDER'"
run docker buildx inspect --bootstrap "$BUILDER"

if [ "$DRY_RUN" -eq 0 ]; then
	available="$(docker buildx inspect "$BUILDER" | grep -i '^Platforms:' || true)"
	missing=""
	IFS=',' read -ra wanted <<< "$PLATFORMS"
	for platform in "${wanted[@]}"; do
		# linux/arm64/v8 aparece como linux/arm64 en la lista del builder
		short="$(echo "$platform" | cut -d/ -f1-2)"
		case "$available" in
			*"$short"*) ;;
			*) missing="$missing $platform" ;;
		esac
	done
	if [ -n "$missing" ]; then
		echo "Error: el builder '$BUILDER' no soporta:$missing" >&2
		echo "Instala los handlers de qemu, por ejemplo:" >&2
		echo "  docker run --privileged --rm tonistiigi/binfmt --install all" >&2
		exit 1
	fi
fi

# --- Compilar y publicar ------------------------------------------------------

for i in "${!PLAN_VERSIONS[@]}"; do
	version="${PLAN_VERSIONS[$i]}"
	build_args=(buildx build --builder "$BUILDER" --platform "$PLATFORMS" \
		--build-arg "FS_VERSION=$version" --pull)
	for tag in ${PLAN_TAGS[$i]}; do
		build_args+=(-t "$IMAGE:$tag")
	done
	build_args+=(--push "$CWD")

	echo
	info "compilando y publicando $version -> ${PLAN_TAGS[$i]// /, }"
	run docker "${build_args[@]}"
done

# --- Actualizar el Dockerfile -------------------------------------------------

if [ "$BUMP" -eq 1 ]; then
	if [ -z "$STABLE" ]; then
		echo "Aviso: --bump necesita --stable, no se toca el Dockerfile." >&2
	else
		info "actualizando ARG FS_VERSION del Dockerfile a $STABLE"
		run sed -i -E "s|^ARG FS_VERSION=.*|ARG FS_VERSION=$STABLE|" "$CWD/Dockerfile"
	fi
fi

echo
info "Terminado."
