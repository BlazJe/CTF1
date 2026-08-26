<?php
require_once '/var/www/inc/session.php';
require_once '/var/www/inc/rate_limit.php';
require_once __DIR__ . '/dev-portal/config.php';

$error = null;
$errorKind = 'error';
$limited = nordvel_rate_limited();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($limited) {
        $wait = nordvel_rate_retry_after();
        $errorKind = 'warn';
        $error = "Preveč neuspešnih poskusov prijave. Ta obrazec ni namenjen ugibanju gesel - "
               . "poskusite znova čez {$wait} s.";
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $authenticated = false;

        if ($username !== '' && $password !== '') {
            mysqli_report(MYSQLI_REPORT_OFF);
            $db = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

            if (!$db->connect_errno) {
                $stmt = $db->prepare('SELECT username, role FROM users WHERE username = ? AND password_md5 = MD5(?)');
                $stmt->bind_param('ss', $username, $password);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    $authenticated = true;
                    nordvel_set_session($row['username'], true, $row['role']);
                    header('Location: ' . ($row['role'] === 'admin' ? '/admin.php' : '/dashboard.php'));
                    exit;
                }
            }
        }

        if (!$authenticated) {
            nordvel_record_attempt();
            $limited = nordvel_rate_limited();
            $error = 'Napačno uporabniško ime ali geslo.';
        }
    }
}
?>
<!doctype html>
<html lang="sl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prijava za stranke - Nordvel d.o.o.</title>
<link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>

<header class="site-header">
  <div class="wrap">
    <a class="brand" href="/"><span class="mark">N</span> Nordvel <span class="suffix">d.o.o.</span></a>
    <nav class="site-nav">
      <a href="/">Nazaj na spletno stran</a>
    </nav>
  </div>
</header>

<div class="auth-page">
  <div class="auth-card">
    <h1>Prijava za stranke</h1>
    <p class="sub">Vpišite poverilnice, ki ste jih prejeli ob sklenitvi pogodbe.</p>

    <?php if ($error !== null): ?>
      <div class="alert alert-<?= $errorKind ?>"><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <form method="post" action="/login.php" autocomplete="off">
      <div class="field">
        <label for="username">Uporabniško ime</label>
        <input type="text" id="username" name="username" autocomplete="username" required>
      </div>
      <div class="field">
        <label for="password">Geslo</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Prijava</button>
    </form>

    <p class="hint-note">
      Portal dovoljuje največ pet poskusov prijave na minuto. Če ste pozabili geslo,
      se obrnite na skrbnika sistema na podpora@nordvel.si.
    </p>
  </div>
</div>

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-bottom" style="border:0;padding-top:0">
      <span>&copy; 2026 Nordvel d.o.o.</span>
      <span>Dunajska cesta 128, 1000 Ljubljana</span>
    </div>
  </div>
</footer>

</body>
</html>
