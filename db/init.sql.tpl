-- Bazo ctfdb ustvari že spremenljivka MYSQL_DATABASE v docker-compose.yml.
USE ctfdb;

CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password_md5 VARCHAR(32)  NOT NULL,
    role         VARCHAR(20)  NOT NULL DEFAULT 'user'
);

CREATE TABLE notes (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    body  TEXT         NOT NULL
);

CREATE TABLE secrets (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    value VARCHAR(255) NOT NULL
);

-- Stopnja 6: nesoljen MD5 sibkega gesla iz seznama rockyou.txt.
INSERT INTO users (username, password_md5, role) VALUES
    ('admin',     '9542b1c8a9396d76bf92e556afda0cfc',      'admin'),
    ('blazj',     MD5('Nordvel-2026!x'), 'user'),
    ('mkovacic',  MD5('Adriales!2025'),  'user'),
    ('servisni',  MD5('S3rvis-nordvel'), 'user');

INSERT INTO notes (title, body) VALUES
    ('Dobrodošli v internem portalu',
     'To je interni sistem zapiskov razvojne ekipe Nordvel d.o.o. Zapiski so vidni vsem članom ekipe, zato sem ne shranjujemo poverilnic naročnikov.'),
    ('Prijava temelji samo na piškotku',
     'Trenutna prijava v naročniški portal preverja zgolj vsebino piškotka, ki ga strežnik niti ne podpiše. Pred produkcijo je treba dodati podpisane seje.'),
    ('Selitev strežnika',
     'Selitev je predvidena za konec meseca. Varnostne kopije zapiskov so začasno shranjene v mapi na spletnem strežniku - po selitvi jih je treba pobrisati.'),
    ('Prenova skrbniške plošče',
     'Obrazec za nalaganje datotek sprejme karkoli in ne preverja končnic. Naloga za naslednjo izdajo: omejiti dovoljene vrste datotek.'),
    ('Nočne varnostne kopije',
     'Skript za varnostne kopije je v mapi /opt/backup na strežniku. Uporabnik devops ga sme zagnati prek sudo, da za to ni potrebno geslo skrbnika.');

-- Stopnja 5: vrednost, do katere se pride prek SQL injekcije.
INSERT INTO secrets (label, value) VALUES
    ('opomba_pregled', 'Testni zapis notranjega pregleda, brez pomena.'),
    ('zastavica_pregled', '@@FLAG_5@@'),
    ('licencni_kljuc', 'NRDV-2026-XKQ4-88MZ');
