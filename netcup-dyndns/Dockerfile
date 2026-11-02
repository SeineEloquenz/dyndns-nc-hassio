FROM ghcr.io/home-assistant/base:3.24

ARG UPSTREAM_VERSION=7.0.1
# Both ARG lines are rewritten by the upstream-release workflow.
ARG UPSTREAM_REF=eb69783e334d87e35b8ff6e4440467fbe3326b09

ARG PHP_PKG=php84

RUN \
    apk add --no-cache "${PHP_PKG}" "${PHP_PKG}-curl" \
    # The php84 package provides no unversioned php command.
    && ln -sf "/usr/bin/${PHP_PKG}" /usr/bin/php \
    && php -r 'exit(extension_loaded("curl") ? 0 : 1);' \
    && mkdir -p /opt/netcup \
    && for file in update.php functions.php; do \
        curl -sSLf -o "/opt/netcup/${file}" \
            "https://raw.githubusercontent.com/stecklars/dynamic-dns-netcup-api/${UPSTREAM_REF}/${file}"; \
    done

COPY rootfs /

LABEL \
    io.hass.type="app" \
    io.hass.name="netcup Dynamic DNS" \
    io.hass.description="Keeps netcup DNS records pointed at your current public IP address" \
    org.opencontainers.image.title="Home Assistant App: netcup Dynamic DNS" \
    org.opencontainers.image.description="Keeps netcup DNS records pointed at your current public IP address" \
    org.opencontainers.image.source="https://github.com/SeineEloquenz/dyndns-nc-hassio" \
    org.opencontainers.image.licenses="MIT" \
    nz.eloque.netcup-dyndns.upstream-version="${UPSTREAM_VERSION}"
