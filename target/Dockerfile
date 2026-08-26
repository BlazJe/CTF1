FROM php:8.2-apache

# --- Sistemska orodja, ki jih zahteva scenarij ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        sudo \
        python3 \
        netcat-openbsd \
        curl \
        procps \
    && rm -rf /var/lib/apt/lists/*

# --- Razširitev za dostop do MariaDB ---
RUN docker-php-ext-install mysqli

# --- Apache: ime strežnika in izklop izpisa vsebine map ---
RUN echo "ServerName ctf.lan" >> /etc/apache2/apache2.conf \
    && sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/' /etc/apache2/apache2.conf

# Kratki poti /dev in /portal vodita na interni razvojni portal, zato ga
# gobuster najde tudi s privzetim seznamom dirb/common.txt. Vzorec je
# zasidran, da ne ujame same poti /dev-portal in ne povzroči zanke.
RUN printf '%s\n' \
    'RedirectMatch 301 "^/(dev|portal)/?$" "/dev-portal/"' \
    '' \
    '# Brez tega Apache postreze .txt brez oznake kodiranja, brskalnik pa' \
    '# pade na Latin-1 in sumnike izpise napacno.' \
    'AddDefaultCharset UTF-8' \
    '' \
    '# Skrita datoteka z zastavico v shrambi ni dosegljiva prek HTTP.' \
    '# Pravilo je namenoma omejeno na /uploads: siroko pravilo bi na vsako' \
    '# piko v korenu vracalo 403 in zasulo izpis gobusterja.' \
    '<Directory /var/www/html/uploads>' \
    '    <FilesMatch "^\.">' \
    '        Require all denied' \
    '    </FilesMatch>' \
    '</Directory>' \
    > /etc/apache2/conf-available/nordvel.conf \
    && a2enconf nordvel

# --- Lokalni uporabnik devops (stopnji 9 in 10) ---
RUN useradd -m -s /bin/bash devops \
    && echo 'devops:Devops2023!' | chpasswd

# --- Aplikacija ---
COPY target/src/ /var/www/html/

# Skupne vkljucene datoteke stojijo IZVEN spletnega korena, da jih Apache
# sploh ne strezhe in da mape /inc ni v izpisu gobusterja.
COPY target/private/ /var/www/inc/
RUN chown -R www-data:www-data /var/www/inc \
    && chmod 755 /var/www/inc \
    && chmod 644 /var/www/inc/*.php

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# --- Stopnja 7: pripravljene datoteke v shrambi ---
# Ime na disku je md5(izvirno_ime_s_koncnico), koncnica ostane nespremenjena.
RUN set -eu; \
    mk() { \
        h=$(printf '%s' "$1" | md5sum | cut -d' ' -f1); \
        printf '%b\n' "$2" > "/var/www/html/uploads/${h}.txt"; \
    }; \
    mk "porocilo.txt" "Nordvel d.o.o. - porocilo o notranjem varnostnem pregledu\nFebruar 2026, pripravil: @@DEV_USER@@\n\nUgotovitev 4.2 - poimenovanje datotek v shrambi\nShramba poimenuje datoteke po pravilu md5(izvirno_ime_s_koncnico), koncnica\nostane nespremenjena. Ime datoteke zato ni skrivnost: kdor pozna izvirno ime,\nlahko pot izracuna sam.\n\nZastavica pregleda: @@FLAG_7@@"; \
    mk "cenik.txt" "Nordvel d.o.o. - cenik storitev 2026\n\nRazvoj aplikacij      68 EUR / uro\nSistemska administracija  62 EUR / uro\nDezurstvo izven delovnega casa  95 EUR / uro\n\nInterno gradivo, ni za javno objavo."; \
    mk "pogodba.txt" "Vzorec pogodbe o vzdrzevanju informacijskega sistema\nOsnutek pravne sluzbe, verzija 3.\n\n1. clen - predmet pogodbe\nIzvajalec se zavezuje, da bo vzdrzeval informacijski sistem narocnika v\nobsegu, dogovorjenem v prilogi A.\n\nOsnutek se ne posreduje izven podjetja."

# --- Zastavice ---
# V repozitoriju so shranjene zakodirane (secrets/flags.b64), da jih ni
# mogoce prebrati z golim pregledom datotek ali z iskanjem po GitHubu.
# Sem se odkodirajo in vstavijo na mesta z oznacbami @@FLAG_N@@.
COPY secrets/flags.b64 /tmp/flags.b64
RUN set -eu; \
    base64 -d /tmp/flags.b64 > /tmp/flags.env; \
    . /tmp/flags.env; \
    \
    grep -rlZ '@@' /var/www/html /var/www/inc | xargs -0 -r sed -i \
        -e "s|@@FLAG_1@@|${FLAG_1}|g" \
        -e "s|@@FLAG_3@@|${FLAG_3}|g" \
        -e "s|@@FLAG_4@@|${FLAG_4}|g" \
        -e "s|@@FLAG_6@@|${FLAG_6}|g" \
        -e "s|@@FLAG_7@@|${FLAG_7}|g" \
        -e "s|@@DEV_USER@@|${DEV_USER}|g" \
        -e "s|@@AUTH_SECRET@@|${AUTH_SECRET}|g"; \
    \
    printf '%s\n' "${FLAG_8}"  > /var/www/html/uploads/.flag; \
    printf '%s\n' "${FLAG_9}"  > /home/devops/user.txt; \
    printf '%s\n' "${FLAG_10}" > /root/root.txt; \
    \
    rm -f /tmp/flags.b64 /tmp/flags.env; \
    \
    if grep -rq '@@FLAG\|@@AUTH_SECRET\|@@DEV_USER' /var/www/html /var/www/inc; then \
        echo 'NAPAKA: nekatere oznacbe niso bile zamenjane' >&2; exit 1; \
    fi

# --- Stopnja 8: zastavica v shrambi, berljiva za www-data ---
# Prek HTTP ni dosegljiva (glej nordvel.conf), zato je potrebno izvajanje kode.
RUN chown www-data:www-data /var/www/html/uploads/.flag \
    && chmod 640 /var/www/html/uploads/.flag \
    && chown www-data:www-data /var/www/html/uploads \
    && chmod 755 /var/www/html/uploads

# --- Stopnja 9: domaca mapa uporabnika devops ---
RUN chown devops:devops /home/devops/user.txt \
    && chmod 600 /home/devops/user.txt

# --- Stopnja 10: backup skript in pravilo sudo brez gesla ---
RUN mkdir -p /opt/backup/lib
COPY target/backup/backup.py /opt/backup/backup.py
COPY target/backup/archiver.py /opt/backup/lib/archiver.py
RUN chown -R root:root /opt/backup \
    && chmod 755 /opt/backup/backup.py \
    && chmod 644 /opt/backup/lib/archiver.py \
    && chown devops:devops /opt/backup/lib \
    && chmod 775 /opt/backup/lib

RUN chmod 600 /root/root.txt

RUN echo 'devops ALL=(root) NOPASSWD: /usr/bin/python3 /opt/backup/backup.py' > /etc/sudoers.d/devops \
    && chmod 440 /etc/sudoers.d/devops \
    && visudo -cf /etc/sudoers.d/devops

EXPOSE 80
