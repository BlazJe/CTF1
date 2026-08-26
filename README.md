# Nordvel CTF Lab

Celotno okolje teče lokalno v Dockerju in ni dosegljivo z drugih naprav.

```bash
git clone https://github.com/BlazJe/CTF1.git nordvel-ctf
cd nordvel-ctf
./setup.sh up
```

---
## Upravljanje

```bash
./setup.sh up      # zgradi, zažene, doda vnosa v /etc/hosts
./setup.sh down    # ustavi vsebnike, odstrani vnosa; podatki ostanejo
./setup.sh reset   # popolnoma počisti (baza, naloženo, napredek) in znova zažene
```

`./reset.sh` je bližnjica za `./setup.sh reset`. Med skupinami uporabi
`reset`, da nova ekipa začne na čistem.


## Opozorilo

Aplikacija je namenoma ranljiva in ni namenjena izpostavljanju v omrežje.
Zaženi jo samo lokalno, kot je nastavljeno privzeto.
