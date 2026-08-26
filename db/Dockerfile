FROM mariadb:10.11

# Shema vsebuje zastavico 5. stopnje, ki je v repozitoriju shranjena
# zakodirana. Odkodira se tu, ob gradnji slike, zato na gostitelju ni
# nobene datoteke z zastavico v cistopisu.
#
# Namenoma NE priklapljamo init.sql z gostitelja: ce datoteka ne bi
# obstajala, bi Docker na njenem mestu ustvaril mapo in baza se ne bi
# inicializirala - tiha in tezko opazna napaka.

COPY secrets/flags.b64 /tmp/flags.b64
COPY db/init.sql.tpl /tmp/init.sql.tpl

RUN set -eu; \
    base64 -d /tmp/flags.b64 > /tmp/flags.env; \
    . /tmp/flags.env; \
    sed -e "s|@@FLAG_5@@|${FLAG_5}|g" /tmp/init.sql.tpl \
        > /docker-entrypoint-initdb.d/init.sql; \
    rm -f /tmp/flags.b64 /tmp/flags.env /tmp/init.sql.tpl; \
    if grep -q '@@FLAG' /docker-entrypoint-initdb.d/init.sql; then \
        echo 'NAPAKA: oznacba v shemi ni bila zamenjana' >&2; exit 1; \
    fi
