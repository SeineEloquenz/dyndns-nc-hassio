FROM ghcr.io/home-assistant/base:3.24

ARG UPSTREAM_VERSION=7.0.0
# The commit the tag above points at. A SHA rather than the tag itself, because
# tags can be moved. Both lines are rewritten by the upstream-release workflow.
ARG UPSTREAM_REF=8e1e33423df69b6e4bb8bd53beaf9cbc442bfd67

RUN \
    apk add --no-cache php84 php84-curl \
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
