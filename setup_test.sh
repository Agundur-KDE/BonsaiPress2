#!/usr/bin/env bash
# Checks the prerequisites BonsaiPress needs before you go looking for a bug
# in the CMS itself: Docker installed and reachable without sudo, the
# `docker compose` v2 plugin present, no malformed proxy env vars breaking
# image pulls/builds, Git installed, and the ports `bonsai start` binds to
# actually free.

set -uo pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

ok()   { echo -e "  ${GREEN}✓${NC} $1"; }
fail() { echo -e "  ${RED}✗${NC} $1"; }
hint() { echo -e "    ${YELLOW}→${NC} $1"; }

echo "BonsaiPress Setup Check"
echo

# 1. Docker installed
if command -v docker >/dev/null 2>&1; then
    ok "docker found ($(command -v docker))"
else
    fail "docker not installed"
    hint "install Docker Desktop (Mac/Windows) or Docker Engine (Linux): https://www.docker.com/products/docker-desktop"
    exit 1
fi

# 2. Docker daemon reachable WITHOUT sudo — the exact call `bonsai` makes.
# A daemon that's simply not running and a daemon the current user has no
# socket permission for print the same generic failure downstream, so we
# distinguish them here by also trying `sudo docker info`.
echo
if docker info >/dev/null 2>&1; then
    ok "docker daemon reachable (no sudo needed)"
else
    if sudo -n docker info >/dev/null 2>&1 || { command -v sudo >/dev/null 2>&1 && sudo docker info >/dev/null 2>&1; }; then
        fail "docker daemon reachable only with sudo — current user lacks permission"
        hint "sudo usermod -aG docker \$USER"
        hint "then log out and back in completely (a new terminal alone is not enough)"
        hint "quick test without re-login: newgrp docker"
    else
        fail "docker daemon not reachable at all"
        hint "start it: Docker Desktop, or on Linux 'sudo systemctl start docker'"
        hint "check with: sudo systemctl status docker"
    fi
fi

# 3. docker compose v2 plugin present. The legacy standalone `docker-compose`
# (v1, hyphenated) does NOT satisfy this — `bonsai` calls `docker compose`
# (space) everywhere, and a missing v2 plugin fails with a confusing
# "unknown shorthand flag: 'f' in -f" instead of a clear "not found".
echo
if docker compose version >/dev/null 2>&1; then
    ok "docker compose plugin found ($(docker compose version --short 2>/dev/null || echo "version unknown"))"
else
    fail "docker compose (v2 plugin) not found or broken"
    hint "Debian/Ubuntu: sudo apt update && sudo apt install docker-compose-plugin"
    hint "if that package isn't available, install manually — no PPA needed:"
    hint "  mkdir -p ~/.docker/cli-plugins/"
    hint "  curl -SL https://github.com/docker/compose/releases/latest/download/docker-compose-linux-x86_64 -o ~/.docker/cli-plugins/docker-compose"
    hint "  chmod +x ~/.docker/cli-plugins/docker-compose"
fi

# 4. Proxy env vars, if set, must be well-formed. Docker silently forwards
# http_proxy/https_proxy into both registry pulls and in-container apt
# builds — a malformed value (missing/misplaced scheme, e.g.
# "127.0.0.1://2080" instead of "http://127.0.0.1:2080") breaks both, and
# shows up as an unrelated-looking "unauthorized" pull error or an apt
# "Unsupported proxy configured" build failure.
echo
PROXY_URL_RE='^(https?|socks[45]?)://[A-Za-z0-9.\-]+(:[0-9]+)?/?$'
PROXY_ISSUES=0
for var in http_proxy https_proxy HTTP_PROXY HTTPS_PROXY; do
    val="${!var:-}"
    [[ -z "$val" ]] && continue
    if [[ "$val" =~ $PROXY_URL_RE ]]; then
        ok "$var is set and well-formed ($val)"
    else
        fail "$var is set but malformed: $val"
        hint "expected form: scheme://host:port, e.g. http://127.0.0.1:2080 or socks5://127.0.0.1:2080"
        PROXY_ISSUES=1
    fi
done
if [[ -z "${http_proxy:-}${https_proxy:-}${HTTP_PROXY:-}${HTTPS_PROXY:-}" ]]; then
    ok "no proxy env vars set"
elif [[ "$PROXY_ISSUES" -eq 0 ]]; then
    hint "proxy is configured — if docker pulls/builds still fail, this is worth double-checking first"
fi

# 5. Git installed
echo
if command -v git >/dev/null 2>&1; then
    ok "git found ($(command -v git))"
else
    fail "git not installed"
    hint "install git via your distro's package manager"
fi

# 6. Ports bonsai binds to are free. Already-running BonsaiPress containers
# also hold these, so a port in use isn't automatically a conflict — check
# `bonsai status` before assuming something else needs to be stopped.
echo
for port in 8080 8081 8001; do
    if command -v ss >/dev/null 2>&1 && ss -ltn 2>/dev/null | grep -q ":${port} "; then
        fail "port $port already in use"
        hint "run 'bonsai status' first — this may just be BonsaiPress already running"
        hint "otherwise stop whatever holds it, or remap the port in compose.yml"
    else
        ok "port $port free"
    fi
done

echo
echo "Done."
