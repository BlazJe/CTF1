<?php
/**
 * Omejevanje poskusov prijave.
 *
 * Namenoma ne uporablja PHP-jeve seje (session_start()), ker bi ta brskalniku
 * dodala še piškotek PHPSESSID. V tem okolju mora biti v brskalniku natanko
 * en piškotek - "session" - da je začetniku jasno, katerega preučuje.
 *
 * Poskusi se štejejo po naslovu IP v datoteki na strežniku.
 */

const NORDVEL_RATE_WINDOW = 60;   // sekunde
const NORDVEL_RATE_MAX = 5;       // dovoljeni poskusi v oknu

function nordvel_rate_file(): string
{
    $dir = sys_get_temp_dir() . '/nordvel-rate';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'neznan';
    return $dir . '/' . md5($ip) . '.json';
}

/** Časovne značke poskusov znotraj opazovanega okna. */
function nordvel_recent_attempts(): array
{
    $file = nordvel_rate_file();
    if (!is_file($file)) {
        return [];
    }

    $data = json_decode((string)@file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }

    $cutoff = time() - NORDVEL_RATE_WINDOW;
    return array_values(array_filter($data, fn($t) => is_int($t) && $t > $cutoff));
}

function nordvel_rate_limited(): bool
{
    return count(nordvel_recent_attempts()) >= NORDVEL_RATE_MAX;
}

function nordvel_record_attempt(): void
{
    $attempts = nordvel_recent_attempts();
    $attempts[] = time();
    @file_put_contents(nordvel_rate_file(), json_encode($attempts), LOCK_EX);
}

/** Koliko sekund do sprostitve omejitve. */
function nordvel_rate_retry_after(): int
{
    $attempts = nordvel_recent_attempts();
    if (count($attempts) < NORDVEL_RATE_MAX) {
        return 0;
    }
    sort($attempts);
    return max(1, $attempts[0] + NORDVEL_RATE_WINDOW - time());
}
