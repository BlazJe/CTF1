# Nordvel CTF Lab

Namerno ranljivo okolje za vajo iz spletne varnosti, v slovenščini in
primerno za začetnike. Deset stopenj v strogem zaporedju: vsaka pusti sled za
naslednjo, zato ni treba ničesar ugibati na slepo.

Celotno okolje teče lokalno v Dockerju in ni dosegljivo z drugih naprav.

```bash
git clone <naslov-repozitorija> nordvel-ctf
cd nordvel-ctf
./setup.sh up
```

To je vse. Skripta zgradi vse tri storitve, jih zažene in doda vnosa v
`/etc/hosts` (edini korak, ki potrebuje `sudo`). Nato odpri
`http://score.lan:8000`, vpiši ime ekipe in začni.

---

## Kaj vsebuje

| Storitev | Vloga | Naslov |
|---|---|---|
| `ctf-target` | ranljiva aplikacija podjetja »Nordvel d.o.o.« (PHP/Apache) | `http://ctf.lan:8080` |
| `ctf-db` | MariaDB s podatki aplikacije | samo znotraj docker omrežja |
| `ctf-scoreboard` | Flask + SQLite za oddajo zastavic | `http://score.lan:8000` |

Deset stopenj pokriva: razkrite datoteke, podatke v izvorni kodi, ponarejanje
piškotka, odkrivanje neobjavljenih poti, SQL injekcijo, razbijanje zgoščenega
gesla, predvidljiva imena datotek, nalaganje datotek brez omejitev, ponovno
uporabljeno geslo in stopnjevanje pravic do roota.

Za reševanje potrebuješ orodja, ki so na Kaliju že nameščena: `curl`,
brskalnik z razvijalskimi orodji, `gobuster`, `sqlmap`, `hashcat` ali `john`
s seznamom `rockyou.txt` in `netcat`.

## Upravljanje

```bash
./setup.sh up      # zgradi, zažene, doda vnosa v /etc/hosts
./setup.sh down    # ustavi vsebnike, odstrani vnosa; podatki ostanejo
./setup.sh reset   # popolnoma počisti (baza, naloženo, napredek) in znova zažene
```

`./reset.sh` je bližnjica za `./setup.sh reset`. Med skupinami uporabi
`reset`, da nova ekipa začne na čistem.

## Omrežna izolacija

Obe objavljeni vrati sta vezani izključno na loopback:

```yaml
ports:
  - "127.0.0.1:8080:80"
  - "127.0.0.1:8000:8000"
```

To je pomembno. Če bi pisalo samo `"8080:80"`, bi Docker vrata odprl na vseh
vmesnikih, vključno z javnim, pravila `ufw` pa bi pri tem obšel, ker Docker
vstavlja svoja pravila DNAT pred njimi. Ker gre za namerno ranljivo
aplikacijo z nalaganjem datotek in potjo do roota, bi bila taka izpostavitev
resna napaka.

Preveri lahko kadarkoli:

```bash
docker compose ps --format "{{.Name}}: {{.Ports}}"
# ctf-target: 127.0.0.1:8080->80/tcp     <- pravilno
# ctf-target: 0.0.0.0:8080->80/tcp       <- narobe, odprto navzven
```

Pri stopnji z nalaganjem datotek veži poslušalca za povratno lupino na docker
prehod (`nc -lvnp 4444 -s 172.28.0.1`), sicer netcat posluša na `0.0.0.0` in
po nepotrebnem odpre vrata navzven.

## Kje so rešitve

Zastavice, opisi nalog in namigi **niso** berljivi iz repozitorija:

- **Zastavice** so shranjene zakodirane v `secrets/flags.b64` in se vstavijo
  šele ob gradnji slik. `grep -r "FLAG{"` po repozitoriju ne vrne ničesar.
- **Naloge, namigi in odgovori** so zakodirani v `secrets/tasks.b64`.
  Scoreboard hrani odgovore kot zgoščene vrednosti SHA256, torej jih tudi iz
  baze ni mogoče prebrati nazaj.
- **Celotna rešitev** je v repozitoriju samo šifrirana, kot
  `WALKTHROUGH.md.enc` (AES-256, PBKDF2).

```bash
./scripts/walkthrough.sh show     # izpiše rešitve, brez zapisa na disk
./scripts/walkthrough.sh open     # odšifrira v WALKTHROUGH.md (v .gitignore)
./scripts/walkthrough.sh lock     # znova zašifrira in pobriše čistopis
```

Privzeto geslo je **`nordvel-mentor`**. Če boš repozitorij delil naprej, ga
zamenjaj: odpri rešitve, nato pa jih z `lock` znova zašifriraj s svojim
geslom.

Naloge lahko urejaš z:

```bash
./scripts/tasks.sh show           # izpiše naloge v berljivem JSON
./scripts/tasks.sh edit           # odpre v urejevalniku in shrani zakodirano
```

### Kaj to dejansko doseže

Zakodiranje ni šifriranje. Ker mora `./setup.sh` teči brez gesla, mora biti
ključ za zastavice v repozitoriju — kdor zna brati `base64` ali pogleda v
zgrajen vsebnik, do njih pride. Namen je preprečiti **naključno pokvarjenje
izziva**: da udeleženec ne dobi odgovorov z brskanjem po datotekah ali z
iskanjem po GitHubu. Pravo skrivnost predstavlja le šifrirani WALKTHROUGH.

Repozitorij je namenjen tistemu, ki okolje postavlja. Udeležencem daj samo
naslov `http://score.lan:8000`.

## Struktura

```
docker-compose.yml
secrets/            zakodirane zastavice in vsebina nalog
db/                 Dockerfile in predloga sheme
target/
  Dockerfile        ranljiva slika, pravice, sudo pravilo
  src/              aplikacija (javna stran, portal, skrbništvo)
  private/          skupne PHP datoteke izven spletnega korena
  backup/           skript za zadnjo stopnjo
scoreboard/         Flask aplikacija in vmesnik
scripts/            walkthrough.sh, tasks.sh
setup.sh reset.sh
WALKTHROUGH.md.enc  šifrirane rešitve
```

## Opozorilo

Aplikacija je namenoma ranljiva in ni namenjena izpostavljanju v omrežje.
Zaženi jo samo lokalno, kot je nastavljeno privzeto.
