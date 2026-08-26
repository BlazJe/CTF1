<?php
require_once '/var/www/inc/session.php';
require_once __DIR__ . '/config.php';

// Enaka zascita kot na seznamu zapiskov: brez seje ni dostopa. Preverja se
// le nepodpisan piskotek, zato ranljivost ostane, vrstni red nalog pa je
// ohranjen - do te strani se ne pride pred 3. stopnjo.
if (!$SESSION_LOGGED_IN) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="sl">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dostop zavrnjen - dev-portal</title>
    <link rel="stylesheet" href="/assets/css/portal.css">
    </head>
    <body>
    <div class="portal-wrap">
      <div class="page-head"><h1>Dostop zavrnjen</h1></div>
      <div class="panel"><div class="panel-body">
        <div class="alert alert-error"><span>Za dostop do internega portala morate biti prijavljeni.</span></div>
        <p><a class="btn" href="/login.php">Na prijavno stran</a></p>
      </div></div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Klasično poročanje napak namesto izjem, da se napaka poizvedbe izpiše
// na strani in ne konča kot PHP "Fatal error".
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

$id = $_GET['id'] ?? '1';

// POZOR: vhod se brez preverjanja vstavi v poizvedbo. Namerna ranljivost.
$sql = "SELECT id, title, body FROM notes WHERE id = $id";
$result = $db->query($sql);

$row = null;
$dbError = null;

if ($result === false) {
    $dbError = $db->error;
} elseif ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
}
?>
<!doctype html>
<html lang="sl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $row ? htmlspecialchars($row['title']) : 'Zapisek' ?> - dev-portal</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/portal.css">
</head>
<body>

<header class="portal-header">
  <div class="bar">
    <a class="brand" href="/dev-portal/">
      <span class="dot"></span> <b>nordvel</b> <span>/ dev-portal</span>
    </a>
    <nav>
      <a href="/dev-portal/">Zapiski</a>
      <a href="/admin.php">Skrbništvo</a>
    </nav>
    <span class="env-tag">Interno okolje</span>
  </div>
</header>

<div class="portal-wrap">

  <div class="page-head">
    <div class="crumb"><a href="/dev-portal/">Zapiski</a> &rsaquo; podrobnosti</div>
    <h1><?= $row ? htmlspecialchars($row['title']) : 'Zapisek' ?></h1>
    <p class="sub">Poizvedba po zaporedni številki zapiska.</p>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Vsebina</h2>
      <?php if ($row): ?><span class="meta">id = <?= htmlspecialchars($row['id']) ?></span><?php endif; ?>
    </div>
    <div class="panel-body">
      <?php if ($dbError !== null): ?>
        <div class="alert alert-error">
          <span>Napaka pri poizvedbi: <?= htmlspecialchars($dbError) ?></span>
        </div>
        <p class="form-hint">Poizvedba se ni izvedla. Preverite vrednost parametra <span class="tag">id</span>.</p>
      <?php elseif ($row): ?>
        <div class="note-body"><?= htmlspecialchars($row['body']) ?></div>
      <?php else: ?>
        <div class="alert alert-info"><span>Zapisek s to številko ne obstaja.</span></div>
      <?php endif; ?>
    </div>
  </div>

  <p><a class="btn btn-quiet" href="/dev-portal/">&larr; Nazaj na seznam</a></p>

</div>

<div class="portal-footer">Nordvel interni sistemi &middot; stran ni namenjena javnosti</div>

</body>
</html>
