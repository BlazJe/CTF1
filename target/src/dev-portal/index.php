<?php
require_once '/var/www/inc/session.php';
require_once __DIR__ . '/config.php';

// Portal je namenjen samo prijavljenim. Preverja se le piškotek, ki ga
// strežnik ne podpiše - ranljivost ostaja enaka, vrstni red nalog pa je s
// tem ohranjen: brez seje iz 3. stopnje se do te strani ne pride.
$authorized = $SESSION_LOGGED_IN;

$notes = [];
if ($authorized) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

    if (!$db->connect_errno) {
        $res = $db->query('SELECT id, title FROM notes ORDER BY id');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $notes[] = $row;
            }
        }
    }
}
?>
<!doctype html>
<html lang="sl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Razvojni portal - Nordvel</title>
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

<?php if (!$authorized): ?>

  <div class="page-head">
    <h1>Dostop zavrnjen</h1>
    <p class="sub">Razvojni portal je namenjen samo prijavljenim uporabnikom.</p>
  </div>
  <div class="panel">
    <div class="panel-body">
      <div class="alert alert-error">
        <span>Za dostop do internega portala morate biti prijavljeni.</span>
      </div>
      <p><a class="btn" href="/login.php">Na prijavno stran</a></p>
    </div>
  </div>

<?php else: ?>

  <div class="page-head">
    <h1>Iskalnik zapiskov</h1>
    <p class="sub">Interno orodje razvojne ekipe. Ni povezano z javne spletne strani in ni namenjeno naročnikom.</p>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Notranji varnostni pregled</h2>
      <span class="meta">pregled 2026-02</span>
    </div>
    <div class="panel-body">
      <div class="flag-box">@@FLAG_4@@</div>
      <p class="flag-caption">Zabeležite vrednost v poročilo o pregledu.</p>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Zapiski ekipe</h2>
      <span class="meta"><?= count($notes) ?> zapiskov</span>
    </div>
    <?php if ($notes): ?>
    <table class="grid">
      <thead>
        <tr><th>ID</th><th>Naslov</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($notes as $note): ?>
        <tr>
          <td class="id">#<?= htmlspecialchars($note['id']) ?></td>
          <td><?= htmlspecialchars($note['title']) ?></td>
          <td style="text-align:right">
            <a href="note.php?id=<?= htmlspecialchars($note['id']) ?>">Odpri &rarr;</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="panel-body">
      <div class="alert alert-error"><span>Zapiskov ni mogoče naložiti - baza ni dosegljiva.</span></div>
    </div>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="panel-body">
      <div class="callout">
        <strong>Opomba:</strong> iskalnik še vedno bere zapiske neposredno po zaporedni številki.
        Prehod na pripravljene poizvedbe je na seznamu nalog za naslednjo izdajo.
      </div>
    </div>
  </div>

<?php endif; ?>

</div>

<div class="portal-footer">Nordvel interni sistemi &middot; stran ni namenjena javnosti</div>

</body>
</html>
