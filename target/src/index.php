<?php require_once '/var/www/inc/session.php'; ?>
<!doctype html>
<html lang="sl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nordvel d.o.o. - razvoj in vzdrževanje poslovnih IT rešitev</title>
<meta name="description" content="Nordvel d.o.o. razvija spletne aplikacije, ureja strežniško infrastrukturo in vzdržuje informacijske sisteme za mala in srednje velika podjetja.">
<link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>

<?php if ($SESSION_LOGGED_IN): ?>
<div class="session-banner">
  <div class="wrap">
    <span>Prijavljeni ste kot <strong><?= htmlspecialchars($SESSION['user'] ?? '') ?></strong>.</span>
    <a href="/dashboard.php">Odprite nadzorno ploščo &rarr;</a>
  </div>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/">
      <span class="mark">N</span> Nordvel <span class="suffix">d.o.o.</span>
    </a>
    <nav class="site-nav">
      <a class="nav-hide" href="#storitve">Storitve</a>
      <a class="nav-hide" href="#pristop">Način dela</a>
      <a class="nav-hide" href="#podjetje">Podjetje</a>
      <a class="btn btn-primary" href="/login.php">Prijava za stranke</a>
    </nav>
  </div>
</header>

<section class="hero">
  <div class="wrap">
    <span class="eyebrow">Partner za digitalizacijo od leta 2014</span>
    <h1>Zanesljive IT rešitve za podjetja, ki nimajo časa za zaplete.</h1>
    <p>Razvijamo poslovne aplikacije po meri, urejamo strežniško infrastrukturo in prevzamemo vzdrževanje sistemov, ki jih vaše podjetje uporablja vsak dan.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="#storitve">Naše storitve</a>
      <a class="btn btn-ghost" href="#podjetje">Spoznajte ekipo</a>
    </div>
  </div>
</section>

<section class="stats">
  <div class="wrap">
    <div class="stats-grid">
      <div class="stat"><span class="num">120+</span><span class="label">izpeljanih projektov</span></div>
      <div class="stat"><span class="num">38</span><span class="label">rednih naročnikov</span></div>
      <div class="stat"><span class="num">12</span><span class="label">let izkušenj</span></div>
      <div class="stat"><span class="num">99,8 %</span><span class="label">razpoložljivost sistemov</span></div>
    </div>
  </div>
</section>

<section class="section" id="storitve">
  <div class="wrap">
    <div class="section-head">
      <div class="kicker">Storitve</div>
      <h2>Pokrivamo celoten življenjski cikel vaše programske opreme</h2>
      <p>Od prve zasnove do vzdrževanja v produkciji. Delamo v majhnih ekipah, ki ostanejo iste od začetka do konca projekta.</p>
    </div>
    <div class="card-grid">
      <div class="card">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>
        <h3>Razvoj poslovnih aplikacij</h3>
        <p>Rešitve po meri za evidence, naročila in interne procese, prilagojene načinu dela vašega podjetja.</p>
      </div>
      <div class="card">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="8" rx="2"/><rect x="2" y="13" width="20" height="8" rx="2"/><line x1="6" y1="7" x2="6.01" y2="7"/><line x1="6" y1="17" x2="6.01" y2="17"/></svg></div>
        <h3>Strežniška infrastruktura</h3>
        <p>Postavitev in nadzor strežnikov, podatkovnih baz ter samodejnih varnostnih kopij.</p>
      </div>
      <div class="card">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
        <h3>Podpora uporabnikom</h3>
        <p>Odzivna tehnična podpora z dogovorjenimi odzivnimi časi in enim samim kontaktom za vsa vprašanja.</p>
      </div>
      <div class="card">
        <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <h3>Varnostni pregledi</h3>
        <p>Redni pregledi aplikacij in strežnikov ter jasna priporočila, kaj popraviti najprej.</p>
      </div>
    </div>
  </div>
</section>

<section class="section alt" id="pristop">
  <div class="wrap">
    <div class="split">
      <div>
        <div class="section-head" style="margin-bottom:0">
          <div class="kicker">Način dela</div>
          <h2>Brez presenečenj na koncu projekta</h2>
          <p>Vsak projekt razdelimo na kratke faze z jasno določenim rezultatom. Po vsaki fazi vidite delujoč izdelek, ne le poročila.</p>
        </div>
        <ul>
          <li>Fiksna cena za vsako fazo, dogovorjena vnaprej.</li>
          <li>Dostop do sistema za sledenje nalogam skozi celoten projekt.</li>
          <li>Predaja kode in dokumentacije v vašo last ob zaključku.</li>
          <li>Dogovorjen odzivni čas za napake v produkciji.</li>
        </ul>
      </div>
      <div class="panel-quote">
        <p>&bdquo;Nordvel je prevzel vzdrževanje sistema, ki ga je pred njimi urejalo troje različnih izvajalcev. V pol leta smo se prvič po dolgem času nehali ukvarjati z izpadi.&ldquo;</p>
        <div class="who"><strong>Maja Kovačič</strong>vodja logistike, Adriales d.o.o.</div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="podjetje">
  <div class="wrap">
    <div class="section-head">
      <div class="kicker">Podjetje</div>
      <h2>Majhna ekipa, ki dela dolgoročno</h2>
      <p>Nordvel d.o.o. je bil ustanovljen leta 2014 v Ljubljani. Danes nas je štirinajst - razvijalci, sistemski administratorji in vodje projektov. Večina naših naročnikov je z nami že več kot pet let, kar je najboljše merilo, ki ga poznamo.</p>
    </div>
    <div class="card-grid">
      <div class="card">
        <h3>Razvoj</h3>
        <p>Šest razvijalcev, ki pokrivajo spletne aplikacije, integracije in podatkovne baze.</p>
      </div>
      <div class="card">
        <h3>Sistemska administracija</h3>
        <p>Štirje inženirji skrbijo za strežnike, omrežja in varnostne kopije naših naročnikov.</p>
      </div>
      <div class="card">
        <h3>Vodenje projektov</h3>
        <p>Vsak naročnik ima enega vodjo projekta, ki ostane isti skozi celotno sodelovanje.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="cta-band">
      <div>
        <h2>Potrebujete izvajalca, ki bo ostal tudi po predaji?</h2>
        <p>Pišite nam na info@nordvel.si ali pokličite 01 234 56 78. Prvi pogovor je brezplačen in nezavezujoč.</p>
      </div>
      <a class="btn" href="mailto:info@nordvel.si">Pošljite povpraševanje</a>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <div class="brand"><span class="mark">N</span> Nordvel <span class="suffix">d.o.o.</span></div>
        <p style="margin:0;max-width:36ch">Razvoj in vzdrževanje poslovnih informacijskih sistemov. Dunajska cesta 128, 1000 Ljubljana.</p>
      </div>
      <div>
        <h4>Storitve</h4>
        <ul>
          <li><a href="#storitve">Razvoj aplikacij</a></li>
          <li><a href="#storitve">Infrastruktura</a></li>
          <li><a href="#storitve">Podpora</a></li>
          <li><a href="#storitve">Varnostni pregledi</a></li>
        </ul>
      </div>
      <div>
        <h4>Za stranke</h4>
        <ul>
          <li><a href="/login.php">Prijava v portal</a></li>
          <li><a href="mailto:podpora@nordvel.si">Prijava napake</a></li>
          <li><a href="mailto:info@nordvel.si">Kontakt</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; 2026 Nordvel d.o.o. Vse pravice pridržane.</span>
      <span>Matična številka 6123456000 &middot; ID za DDV SI12345678</span>
    </div>
  </div>
</footer>

<!-- build: @@DEV_USER@@@nordvel - odstrani pred produkcijo -->

</body>
</html>
