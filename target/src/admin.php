<?php
require_once '/var/www/inc/session.php';

$authorized = $SESSION_LOGGED_IN
    && $SESSION_USER === 'admin'
    && $SESSION_ROLE === 'admin';

$uploadsDir = __DIR__ . '/uploads';
$uploadMessage = null;
$uploadKind = 'ok';

if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['datoteka'])) {
    $file = $_FILES['datoteka'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $original = basename($file['name']);
        $ext = pathinfo($original, PATHINFO_EXTENSION);

        // Shramba poimenuje datoteke po pravilu md5(izvirno_ime_s_koncnico),
        // končnica ostane nespremenjena. Vrste datoteke ne preverjamo.
        $stored = md5($original) . ($ext !== '' ? '.' . $ext : '');

        if (@move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $stored)) {
            $uploadMessage = 'Datoteka je bila naložena.';
        } else {
            $uploadKind = 'error';
            $uploadMessage = 'Datoteke ni bilo mogoče shraniti.';
        }
    } else {
        $uploadKind = 'error';
        $uploadMessage = 'Pri nalaganju datoteke je prišlo do napake.';
    }
}

$downloads = [
    'porocilo.txt' => ['Poročilo o varnostnem pregledu', 'notranji pregled, februar 2026'],
    'cenik.txt'    => ['Cenik storitev 2026', 'interno gradivo, ni za objavo'],
    'pogodba.txt'  => ['Vzorec pogodbe o vzdrževanju', 'osnutek pravne službe'],
];
?>
<!doctype html>
<html lang="sl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Skrbniška plošča - Nordvel</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>

<header class="portal-header">
  <div class="bar">
    <a class="brand" href="/admin.php">
      <span class="dot"></span> <b>nordvel</b> <span>/ admin</span>
    </a>
    <nav>
      <a href="/dev-portal/">Zapiski</a>
      <a href="/admin.php">Skrbništvo</a>
    </nav>
    <span class="env-tag">Interno okolje</span>
  </div>
</header>

<div class="portal-wrap">

<?php if (!$authorized): ?>

  <div class="page-head">
    <h1>Dostop zavrnjen</h1>
    <p class="sub">Ta stran je namenjena samo skrbnikom sistema.</p>
  </div>
  <div class="panel">
    <div class="panel-body">
      <div class="alert alert-error"><span>Nimate pravic za dostop do skrbniške plošče.</span></div>
      <p><a class="btn" href="/login.php">Na prijavno stran</a></p>
    </div>
  </div>

<?php else: ?>

  <div class="page-head">
    <h1>Skrbniška plošča</h1>
    <p class="sub">Prijavljeni ste kot <span class="tag">admin</span>. Spodnja orodja vplivajo na produkcijski strežnik.</p>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Notranji varnostni pregled</h2>
      <span class="meta">pregled 2026-02</span>
    </div>
    <div class="panel-body">
      <div class="flag-box">@@FLAG_6@@</div>
      <p class="flag-caption">Zabeležite vrednost v poročilo o pregledu.</p>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Prenosi</h2>
      <span class="meta"><?= count($downloads) ?> datoteke</span>
    </div>
    <ul class="dl-list">
      <?php foreach ($downloads as $original => [$label, $note]): ?>
        <li>
          <span class="name"><?= htmlspecialchars($label) ?><small><?= htmlspecialchars($note) ?></small></span>
          <a class="path" href="/uploads/<?= md5($original) ?>.txt">/uploads/<?= md5($original) ?>.txt</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Nalaganje datotek</h2>
      <span class="meta">shramba: /uploads</span>
    </div>
    <div class="panel-body">
      <?php if ($uploadMessage !== null): ?>
        <div class="alert alert-<?= $uploadKind ?>"><span><?= htmlspecialchars($uploadMessage) ?></span></div>
      <?php endif; ?>

      <form method="post" action="/admin.php" enctype="multipart/form-data">
        <div class="form-row">
          <label for="datoteka">Izberite datoteko za nalaganje</label>
          <input type="file" id="datoteka" name="datoteka" required>
          <p class="form-hint">Datoteka se shrani v skupno shrambo. Naloženih datotek sistem iz varnostnih razlogov ne izpisuje.</p>
        </div>
        <button class="btn" type="submit">Naloži datoteko</button>
      </form>
    </div>
  </div>

<?php endif; ?>

</div>

<div class="portal-footer">Nordvel interni sistemi &middot; stran ni namenjena javnosti</div>

</body>
</html>
