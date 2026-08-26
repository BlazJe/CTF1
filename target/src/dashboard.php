<?php
require_once '/var/www/inc/session.php';

// Strežnik piškotku verjame brez preverjanja - podpisa ni, zato zadostuje,
// da vsebina pravilno "trdi", kdo je obiskovalec.
$authorized = $SESSION_LOGGED_IN
    && $SESSION_USER === 'blazj'
    && $SESSION_ROLE === 'user';
?>
<!doctype html>
<html lang="sl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nadzorna plošča - Nordvel d.o.o.</title>
<link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/"><span class="mark">N</span> Nordvel <span class="suffix">d.o.o.</span></a>
    <nav class="site-nav">
      <a href="/">Spletna stran</a>
      <a href="/login.php">Prijava</a>
    </nav>
  </div>
</header>

<div class="board-page">
  <div class="wrap">
    <div class="board-card">

      <?php if ($authorized): ?>
        <h1>Nadzorna plošča</h1>
        <div class="alert alert-ok">
          <span>Prijava je potrjena za uporabnika <strong><?= htmlspecialchars($SESSION['user'] ?? '') ?></strong>.</span>
        </div>

        <p>Dostop do naročniškega pregleda je odobren. Spodnja vrednost je namenjena
           notranjemu varnostnemu pregledu.</p>

        <div class="flag-box">@@FLAG_3@@</div>

        <p class="hint-note">
          Obvestilo ekipe: interni razvojni portal je še v pripravi, ni povezan z menija.
          Do njega dostopajte neposredno prek naslova, dokler ga ne uvrstimo v navigacijo.
        </p>

      <?php else: ?>
        <h1>Dostop zavrnjen</h1>
        <div class="alert alert-error">
          <span>Za ogled nadzorne plošče morate biti prijavljeni kot naročnik.</span>
        </div>
        <p>Če menite, da gre za napako, se prijavite znova ali se obrnite na skrbnika sistema.</p>
        <p><a class="btn btn-primary" href="/login.php">Na prijavno stran</a></p>
      <?php endif; ?>

    </div>
  </div>
</div>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-bottom" style="border:0;padding-top:0">
      <span>&copy; 2026 Nordvel d.o.o.</span>
      <span>Naročniški portal</span>
    </div>
  </div>
</footer>

</body>
</html>
